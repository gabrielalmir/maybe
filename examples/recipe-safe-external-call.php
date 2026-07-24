<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maybe\Result\Result;

/**
 * Wrap any exception-throwing legacy call into a Result, without changing
 * the legacy function itself. Useful at the boundary between Maybe-typed
 * code and third-party SDKs, legacy libraries, or PECL extensions that
 * only communicate failure via exceptions.
 *
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

/** Simulates a legacy payment gateway SDK that throws on failure. */
function legacyChargeCard(string $token, int $amountInCents): string
{
    if ($token === 'tok_declined') {
        throw new \RuntimeException('card_declined');
    }

    if ($amountInCents <= 0) {
        throw new \InvalidArgumentException('invalid_amount');
    }

    return 'ch_' . substr(sha1($token . $amountInCents), 0, 10);
}

$charge = static function (string $token, int $amountInCents): Result {
    return tryCatch(static function () use ($token, $amountInCents): string {
        return legacyChargeCard($token, $amountInCents);
    })->mapErr(static fn (\Throwable $e): string => $e->getMessage());
};

foreach (
    [
        ['token' => 'tok_visa', 'amount' => 4990],
        ['token' => 'tok_declined', 'amount' => 4990],
        ['token' => 'tok_visa', 'amount' => 0],
    ] as $attempt
) {
    echo $charge($attempt['token'], $attempt['amount'])->match(
        static fn (string $chargeId): string => "charged: {$chargeId}",
        static fn (string $error): string => "failed: {$error}"
    ) . "\n";
}
