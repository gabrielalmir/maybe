# Result

`Result<T, E>` represents an operation that either succeeded with a value (`Ok`) or failed with a typed error (`Err`) — no exceptions as control flow.

## Creating a Result

```php
use Maybe\Result\Result;

function loadUser(int $id): Result
{
    if ($id <= 0) {
        return Result::err('invalid_id');
    }

    return Result::ok(['id' => $id, 'name' => 'Ana']);
}
```

## Chaining fallible operations

`andThen` is the workhorse: it chains another operation that can also fail, short-circuiting on the first `Err`.

```php
$invoice = loadUser(10)
    ->andThen(fn (array $user): Result => checkSubscription($user))
    ->andThen(fn (array $user): Result => createInvoice($user))
    ->map(fn (array $invoice): array => addTotals($invoice));
```

`orElse` is the mirror image — recover from an error by producing a new `Result`:

```php
$user = loadFromCache($id)
    ->orElse(fn (string $error): Result => loadFromDatabase($id));
```

`mapErr` transforms the error payload without touching success values:

```php
$result = loadUser($id)->mapErr(fn (string $e): array => ['code' => $e]);
```

## Extracting values

```php
$result->unwrap();                          // value, or throws UnwrapErrException on Err
$result->unwrapErr();                       // error, or throws UnwrapOkException on Ok
$result->unwrapOr($default);                // value or eager fallback
$result->unwrapOrElse(fn ($e) => recover($e)); // value or fallback computed from error
$result->expect('user must exist');         // value, or throws with your message
```

Prefer `match` when both branches need handling:

```php
$message = loadUser(10)->match(
    fn (array $user): string => 'User: ' . $user['name'],
    fn (string $error): string => 'Error: ' . $error
);
```

## Converting to Option

```php
$result->okOption();   // Ok(v) -> Some(v), Err(_) -> None
$result->errOption();  // Err(e) -> Some(e), Ok(_) -> None
```

## API summary

| Method | Description |
| --- | --- |
| `Result::ok($v)` / `Result::err($e)` | Constructors |
| `map(fn)` / `mapErr(fn)` | Transform success / error |
| `andThen(fn)` / `orElse(fn)` | Chain fallible operations / recover |
| `match(onOk, onErr)` | Exhaustive handling |
| `unwrap()` / `unwrapErr()` / `unwrapOr($d)` / `unwrapOrElse(fn)` / `expect($msg)` | Extraction |
| `okOption()` / `errOption()` | Convert to `Option` |
| `isOk()` / `isErr()` | Inspection |
