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

**With Maybe:** name each boundary and keep the caller focused on the outcome:

```php
$emailResult = $emailSchema->safeParse($message)
    ->andThen(static fn (array $valid): Result => sendWithFallback($valid));

$emailResult->match(
    static fn (string $ref): string => "sent ({$ref})",
    static fn (array $error): string => $error['retryable']
        ? "queued for retry ({$error['reason']})"
        : "rejected: fix the input ({$error['reason']})"
);
```

The transport details live in `sendWithFallback()`, which can be tested independently. The page that confirms the order only has to decide what an `Ok` or an `Err` means.

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

**With Maybe:** keep validation, transport and routing as three named boundaries:

```php
$sapResult = $orderSchema->safeParse($order)
    ->andThen(static fn (array $payload): Result => postToSap($payload));

$sapResult->match(
    static fn (string $document): string => "created in SAP ({$document})",
    static fn (array $error): string => routeSapFailure($order, $error)
);
```

`postToSap()` can classify a connection error as retryable while `routeSapFailure()` decides between retry and manual review. Neither decision is hidden inside a controller.

**Why this matters:** the order is confirmed locally either way — SAP being down doesn't take checkout down with it — but a business error (unknown material, missing cost center) stops being retried forever instead of quietly failing the same way on every retry attempt.

**When not to use this pattern:** if your process genuinely cannot proceed without SAP confirmation first (e.g. real-time stock allocation), don't decouple it — make the SAP call synchronous and part of the same transaction boundary.

Full runnable file: [`examples/scenario-sap-order-integration.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/scenario-sap-order-integration.php)

## 3. Contract validation with cross-field business rules

**The business risk.** Contract validation scattered across a controller as a chain of `if` statements lets a contract get half-saved in an invalid state, and produces error messages too unstructured for a legal/ops review screen to point at the exact offending field.

**A real limitation worth knowing:** `Schema` has no built-in cross-field validation (e.g. "the end date must be after the start date") or conditional required-list checks. The idiomatic fix is to add a plain business-rule function after `safeParse()`:

```php
$result = $contractSchema->safeParse($input)
    ->andThen('checkBusinessRules');

$result->match(
    static fn (array $valid): string => "approved (value: {$valid['value_in_cents']} cents)",
    static fn (ValidationErrorBag $errors): string => implode("\n", $errors->describe())
);
```

Both stages return the same `Result` shape, so structural and business-rule errors share one boundary:

```php
$result->match(
    static fn (array $valid): string => renderApproved($valid),
    static fn (ValidationErrorBag $errors): string => renderErrors($errors)
);
```

**Why this matters:** a legal/ops review UI can render every rejection reason — whether it came from the schema stage or the business-rule stage — from the exact same `ValidationErrorBag::toArray()` shape, with a JSONPath-like `path()` pointing at the offending field.

**When not to use this pattern:** don't reach for a business-rule `andThen()` stage for something Schema already expresses natively (e.g. a bounded `int` or a `regex()` — use the schema modifier, don't reinvent it in a closure).

Full runnable file: [`examples/scenario-contract-validation.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/scenario-contract-validation.php)
