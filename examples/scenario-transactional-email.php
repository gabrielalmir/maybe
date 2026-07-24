<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maybe\Result\Result;
use Maybe\Schema\Schema;
use Maybe\Schema\ValidationErrorBag;

/**
 * SCENARIO: order confirmation email.
 *
 * The business rule: a failed transactional email must NEVER roll back an
 * already-confirmed order. It must also never be silently swallowed — it
 * has to become a trackable, retryable failure. Legacy code usually gets
 * this wrong in one of two ways:
 *
 *   @mail($to, $subject, $body);                 // failure is invisible
 *
 *   try {
 *       $mailer->send($to, $subject, $body);
 *   } catch (\Exception $e) {
 *       error_log($e->getMessage());              // logged, then forgotten
 *   }
 *
 * Neither tells the caller whether the order confirmation is "fully done"
 * or "done, but the customer wasn't notified yet". Result makes that
 * distinction explicit and forces the caller to decide what to do about it.
 */

/**
 * @template T
 * @param callable(): T $fn
 * @return Result<T,\Throwable>
 */
function tryCatch(callable $fn): Result
{
    try {
        return Result::ok($fn());
    } catch (\Throwable $e) {
        return Result::err($e);
    }
}

final class SmtpException extends \RuntimeException
{
}

/** Simulates a flaky primary SMTP relay and a more reliable secondary one. */
final class SmtpClient
{
    /** @var string */
    private $host;

    public function __construct(string $host)
    {
        $this->host = $host;
    }

    public function send(string $to, string $subject, string $body): string
    {
        if ($this->host === 'smtp-primary.internal' && strpos($to, 'flaky') !== false) {
            throw new SmtpException('connection timed out after 5s');
        }

        return sprintf('%s:%s', $this->host, substr(sha1($to . $subject), 0, 8));
    }
}

$emailSchema = Schema::shape([
    'to' => Schema::string()->trimmed()->regex('/^[^@\s]+@[^@\s]+\.[^@\s]+$/'),
    'subject' => Schema::string()->trimmed()->min(3),
    'body' => Schema::string()->trimmed()->min(10),
]);

$primary = new SmtpClient('smtp-primary.internal');
$secondary = new SmtpClient('smtp-secondary.internal');

/**
 * Validates the message once, then tries the primary relay and falls back
 * to the secondary one on failure — the caller only sees the final Result.
 *
 * A malformed message (validation error) and a transient SMTP failure are
 * NOT the same kind of error: the first will never succeed on retry, the
 * second might. The error payload keeps that distinction explicit instead
 * of collapsing both into a bare string.
 *
 * @param array{to:string,subject:string,body:string} $message
 * @return Result<string,array{retryable:bool,reason:string}>
 */
$sendConfirmationEmail = static function (array $message) use ($emailSchema, $primary, $secondary): Result {
    return $emailSchema->safeParse($message)
        ->mapErr(static function (ValidationErrorBag $errors): array {
            return ['retryable' => false, 'reason' => $errors->summary()];
        })
        ->andThen(static function (array $valid) use ($primary, $secondary): Result {
            return tryCatch(static function () use ($primary, $valid): string {
                return $primary->send($valid['to'], $valid['subject'], $valid['body']);
            })
                ->mapErr(static fn (\Throwable $e): string => $e->getMessage())
                ->orElse(static function () use ($secondary, $valid): Result {
                    return tryCatch(static function () use ($secondary, $valid): string {
                        return $secondary->send($valid['to'], $valid['subject'], $valid['body']);
                    })->mapErr(static fn (\Throwable $e): string => $e->getMessage());
                })
                ->mapErr(static function (string $reason): array {
                    return ['retryable' => true, 'reason' => $reason];
                });
        });
};

/**
 * The order is confirmed regardless of the email outcome — email delivery
 * is a side effect, not a precondition for the order to exist.
 */
$confirmOrder = static function (array $order) use ($sendConfirmationEmail): array {
    $emailResult = $sendConfirmationEmail([
        'to' => $order['email'],
        'subject' => 'Order #' . $order['id'] . ' confirmed',
        'body' => 'Thanks for your order, ' . $order['customer'] . '!',
    ]);

    return [
        'order_id' => $order['id'],
        'order_status' => 'confirmed',
        'email' => $emailResult->match(
            static fn (string $ref): string => "sent ({$ref})",
            static function (array $error): string {
                return $error['retryable']
                    ? "queued for retry ({$error['reason']})"
                    : "rejected, needs a data fix ({$error['reason']})";
            }
        ),
    ];
};

foreach (
    [
        ['id' => 1001, 'customer' => 'Ana Souza', 'email' => 'ana@example.com'],
        ['id' => 1002, 'customer' => 'Bruno Lima', 'email' => 'flaky@example.com'],
        ['id' => 1003, 'customer' => 'Carla Melo', 'email' => 'not-an-email'],
    ] as $order
) {
    $outcome = $confirmOrder($order);

    printf(
        "order #%d: %s | email: %s\n",
        $outcome['order_id'],
        $outcome['order_status'],
        $outcome['email']
    );
}
