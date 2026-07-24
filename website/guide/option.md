# Option

`Option<T>` represents a value that may or may not exist — `Some(value)` or `None` — replacing `null` checks with an explicit, chainable API.

## Creating an Option

```php
use Maybe\Option\Option;

$some = Option::some('ana');
$none = Option::none();

// The most common entry point: convert a nullable into an Option
$name = Option::fromNullable($payload['name'] ?? null);
```

## Transforming values

```php
$name = Option::fromNullable($payload['name'] ?? null)
    ->map('trim')
    ->flatMap(fn (string $value): Option =>
        $value === '' ? Option::none() : Option::some($value)
    )
    ->unwrapOr('guest');
```

- `map(fn)` transforms the inner value. If the callback returns `null`, the result collapses to `None` (same semantics as `fromNullable`).
- `flatMap(fn)` chains an operation that itself returns an `Option`.
- `filter(predicate)` keeps the value only if the predicate holds.

```php
$adult = Option::some($age)->filter(fn (int $a): bool => $a >= 18);
```

## Extracting values

```php
$option->unwrap();                       // value, or throws UnwrapNoneException on None
$option->unwrapOr('default');            // value or eager fallback
$option->unwrapOrElse(fn () => costly());// value or lazy fallback
$option->expect('name is required');     // value, or throws with your message
```

Prefer `match` when both branches need handling:

```php
$greeting = $option->match(
    fn (string $name): string => "Hello, {$name}",
    fn (): string => 'Hello, guest'
);
```

## Converting to Result

When absence should become a typed error, convert to a [`Result`](/guide/result):

```php
$result = Option::fromNullable($user)->okOr('user_not_found');
$result = Option::fromNullable($user)->okOrElse(fn () => makeError());
```

## API summary

| Method | Description |
| --- | --- |
| `Option::some($v)` / `Option::none()` / `Option::fromNullable($v)` | Constructors |
| `map(fn)` / `flatMap(fn)` / `filter(fn)` | Transformations |
| `match(onSome, onNone)` | Exhaustive handling |
| `unwrap()` / `unwrapOr($d)` / `unwrapOrElse(fn)` / `expect($msg)` | Extraction |
| `okOr($err)` / `okOrElse(fn)` | Convert to `Result` |
| `isSome()` / `isNone()` | Inspection |
