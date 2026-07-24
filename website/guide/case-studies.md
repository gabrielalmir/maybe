# Case Studies

The [Recipes](/guide/recipes) page shows isolated snippets. This page walks through three real corporate scenarios end to end — the business risk, the legacy code that gets it wrong, and how Maybe's types make the failure mode impossible to ignore. Every snippet below is copied from a runnable file in [`examples/`](https://github.com/gabrielalmir/maybe/tree/main/examples), verified against the library's actual source.

## 1. Transactional email that can't break checkout

**The business risk.** An order-confirmation email fails to send. If that failure isn't handled deliberately, one of two bad things happens: the whole checkout crashes over a non-critical side effect, or the failure is silently swallowed and nobody ever finds out the customer wasn't notified.

**What this looks like in legacy code:**

```php
// Silently invisible:
@mail($to, $subject, $body);

// "Handled", but the outcome is thrown away:
try {
    $mailer->send($to, $subject, $body);
} catch (\Exception $e) {
    error_log($e->getMessage());
}
```

Both versions leave the caller with no way to know whether the customer was actually notified — and no way to distinguish "the email address was malformed" (retrying won't help) from "the SMTP relay timed out" (retrying might).

**With Maybe:** validate the message before spending a network call, wrap the SMTP send in a `Result`, fall back to a secondary relay with `orElse()`, and confirm the order regardless of the email outcome:

```php
$sendConfirmationEmail = static function (array $message) use ($emailSchema, $primary, $secondary): Result {
    return $emailSchema->safeParse($message)
        ->mapErr(fn (ValidationErrorBag $errors): array => ['retryable' => false, 'reason' => $errors->summary()])
        ->andThen(function (array $valid) use ($primary, $secondary): Result {
            return tryCatch(fn () => $primary->send($valid['to'], $valid['subject'], $valid['body']))
                ->mapErr(fn (\Throwable $e): string => $e->getMessage())
                ->orElse(fn () => tryCatch(fn () => $secondary->send($valid['to'], $valid['subject'], $valid['body']))
                    ->mapErr(fn (\Throwable $e): string => $e->getMessage()))
                ->mapErr(fn (string $reason): array => ['retryable' => true, 'reason' => $reason]);
        });
};

// The order is confirmed either way — email delivery is a side effect,
// not a precondition for the order to exist.
$emailResult->match(
    fn (string $ref): string => "sent ({$ref})",
    fn (array $error): string => $error['retryable']
        ? "queued for retry ({$error['reason']})"
        : "rejected, needs a data fix ({$error['reason']})"
);
```

**Why this matters:** the error payload keeps `retryable` explicit. A malformed email address and a flaky SMTP relay are *different problems* — one needs a data fix, the other needs a retry queue — and the type keeps them from being handled identically by accident.

**When not to use this pattern:** if email delivery genuinely must block the transaction (e.g. a one-time password that the user needs immediately), don't decouple it — that's a case where failure *should* propagate.

Full runnable file: [`examples/scenario-transactional-email.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/scenario-transactional-email.php)

## 2. Pushing orders into SAP without losing data silently

**The business risk.** A confirmed order needs to be posted into SAP (via RFC/OData/BAPI). SAP calls fail for structured reasons: a duplicate document, a missing cost center, an expired session, a network timeout. Legacy integration code tends to collapse all of them into the same non-answer:

```php
if (!$sap->post($payload)) {
    return false; // which error? nobody knows.
}
```

The real risk here isn't the error itself — it's that the order gets confirmed to the customer, is never created in SAP, and nobody notices until finance reconciliation weeks later.

**With Maybe:** validate the outbound payload against SAP's expected shape *before* the network round-trip, then classify the failure by exception type — a connection problem is retryable, a business rule violation is not:

```php
final class SapConnectionException extends \RuntimeException {} // retryable
final class SapBusinessException extends \RuntimeException {}   // not retryable

$pushOrderToSap = static function (array $order) use ($orderSchema, $sap): Result {
    return $orderSchema->safeParse($order)
        ->mapErr(fn (ValidationErrorBag $errors): array => ['retryable' => false, 'reason' => 'invalid_payload: ' . $errors->summary()])
        ->andThen(function (array $payload) use ($sap): Result {
            return tryCatch(fn () => $sap->postSalesOrder($payload))
                ->mapErr(fn (\Throwable $e): array => [
                    'retryable' => $e instanceof SapConnectionException,
                    'reason' => $e->getMessage(),
                ]);
        });
};
```

The caller routes the outcome into two buckets instead of one undifferentiated failure log:

```php
$sapResult->match(
    fn (string $sapDocNumber): string => "created in SAP ({$sapDocNumber})",
    function (array $error) use ($order, &$requeued, &$manualReview): string {
        if ($error['retryable']) {
            $requeued[] = $order['id'];
            return "requeued for retry ({$error['reason']})";
        }

        $manualReview[] = $order['id'];
        return "sent to manual review ({$error['reason']})";
    }
);
```

**Why this matters:** the order is confirmed locally either way — SAP being down doesn't take checkout down with it — but a business error (unknown material, missing cost center) stops being retried forever instead of quietly failing the same way on every retry attempt.

**When not to use this pattern:** if your process genuinely cannot proceed without SAP confirmation first (e.g. real-time stock allocation), don't decouple it — make the SAP call synchronous and part of the same transaction boundary.

Full runnable file: [`examples/scenario-sap-order-integration.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/scenario-sap-order-integration.php)

## 3. Contract validation with cross-field business rules

**The business risk.** Contract validation scattered across a controller as a chain of `if` statements lets a contract get half-saved in an invalid state, and produces error messages too unstructured for a legal/ops review screen to point at the exact offending field.

**A real limitation worth knowing:** `Schema` has no built-in cross-field validation (e.g. "the end date must be after the start date") or conditional required-list checks (e.g. "these clauses must all be present"). The idiomatic fix is **not** a bigger schema API — it's chaining a plain `Result`-returning business-rule function with `andThen()` right after `safeParse()`, reusing the same `ValidationErrorBag` so both stages report through one uniform error shape:

```php
function checkBusinessRules(array $contract): Result
{
    $errors = new ValidationErrorBag();

    if ($contract['ends_at'] <= $contract['starts_at']) {
        $errors = $errors->withError(
            new ValidationError(Path::field('ends_at'), 'End date must be after the start date', 'contract.invalid_period')
        );
    }

    foreach (array_diff(MANDATORY_CLAUSES, $contract['clauses']) as $clause) {
        $errors = $errors->withError(
            new ValidationError(Path::field('clauses'), "Missing mandatory clause: {$clause}", 'contract.missing_clause')
        );
    }

    return $errors->isEmpty() ? Result::ok($contract) : Result::err($errors);
}

$validateContract = static function (array $input) use ($contractSchema): Result {
    return $contractSchema->safeParse($input)->andThen('checkBusinessRules');
};
```

Because both stages return `Result<array, ValidationErrorBag>`, the caller handles structural errors (invalid tax ID format, contract value below zero) and business-rule errors (invalid date range, missing mandatory clause) through the exact same `match()`:

```php
$result->match(
    fn (array $valid): string => "approved (value: {$valid['value_in_cents']} cents)",
    function (ValidationErrorBag $errors): string {
        return implode("\n", $errors->describe());
    }
);
```

**Why this matters:** a legal/ops review UI can render every rejection reason — whether it came from the schema stage or the business-rule stage — from the exact same `ValidationErrorBag::toArray()` shape, with a JSONPath-like `path()` pointing at the offending field.

**When not to use this pattern:** don't reach for a business-rule `andThen()` stage for something Schema already expresses natively (e.g. a bounded `int` or a `regex()` — use the schema modifier, don't reinvent it in a closure).

Full runnable file: [`examples/scenario-contract-validation.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/scenario-contract-validation.php)
