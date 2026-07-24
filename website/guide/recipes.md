# Recipes

Runnable, self-contained scripts for common problems. Every file lives in [`examples/`](https://github.com/gabrielalmir/maybe/tree/main/examples) in the repository and can be run directly with `php examples/<file>.php` once dependencies are installed (`composer install`).

## Checkout flow with coupons and payment authorization

`Option` for an optional coupon code, `Result` chaining a discount step into a payment authorization step.

[`examples/option-result.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/option-result.php)

## Customer registration with a full DTO

A complete `DTO` example: schema with `trimmed()`, `regex()`, bounded `int`, an optional field via `Schema::option()`, and structured error reporting through `ValidationErrorBag`.

[`examples/schema-dto.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/schema-dto.php)

## Repository lookup with Option

A repository returns `Option` instead of `null`; a service layer converts absence into a typed `Result` error only where it actually needs one, using `okOr()` and `andThen()`.

```php
final class CustomerRepository
{
    public function findById(int $id): Option
    {
        // ...
        return Option::none();
    }
}

$service = static function (CustomerRepository $repo, int $id): Result {
    return $repo->findById($id)
        ->okOr('customer_not_found')
        ->andThen(fn (array $customer): Result =>
            $customer['active'] ? Result::ok($customer['name']) : Result::err('customer_inactive')
        );
};
```

[`examples/recipe-repository-lookup.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/recipe-repository-lookup.php)

## Wrapping a legacy, exception-throwing call

At a boundary with a third-party SDK or legacy code that only communicates failure via exceptions, wrap the call once instead of letting exceptions leak into `Result`-typed code:

```php
function tryCatch(callable $fn): Result
{
    try {
        return Result::ok($fn());
    } catch (\Throwable $e) {
        return Result::err($e);
    }
}
```

[`examples/recipe-safe-external-call.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/recipe-safe-external-call.php)

## Batch import with per-row error reporting

Validate an entire batch (e.g. a CSV import) in one pass with `Schema::arrayOf()` — errors report the row index in the path (`$[1].email`). For partial imports, validate row by row instead and keep both an imported and a rejected bucket.

[`examples/recipe-batch-import.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/recipe-batch-import.php)

## More async recipes

- [`examples/async-basic.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/async-basic.php) — a single background task
- [`examples/async-all-race.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/async-all-race.php) — `Async::all()` and `Async::race()`
- [`examples/async-pool.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/async-pool.php) — bounded concurrency with `Async::pool()`
- [`examples/async-chain-timeout-cancel.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/async-chain-timeout-cancel.php) — `then()`/`catch()`/`finally()`, timeouts and cancellation

For more before/after patterns (validating filters, standardizing JSON error responses, converting legacy `null`/`false` returns), see the [Practical Recipes guide](https://github.com/gabrielalmir/maybe/blob/main/docs/04-practical-recipes.md) in the repository.

## Using an AI assistant with Maybe

If you're using an AI coding assistant (Claude, Copilot, Cursor, etc.) to write code against this library, point it at [`llms.txt`](/llms.txt) — a condensed, exact API reference meant for LLM consumption, so it doesn't have to guess method names or signatures.
