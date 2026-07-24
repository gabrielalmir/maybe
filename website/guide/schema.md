# Schema

`Schema` provides immutable, composable parsing and validation, inspired by Zod. Build a schema once, reuse it everywhere, and get structured errors instead of exceptions.

## Basic usage

```php
use Maybe\Schema\Schema;

$schema = Schema::shape([
    'email' => Schema::string()->trimmed()->min(5),
    'age' => Schema::int()->min(18),
]);

$result = $schema->safeParse([
    'email' => '  user@example.com  ',
    'age' => 23,
]);
```

`safeParse` returns a `Result`: `Ok` with the validated (and transformed — e.g. trimmed) data, or `Err` with a `ValidationErrorBag`.

```php
$result->match(
    fn (array $data) => saveUser($data),
    fn ($errors) => respondWith422($errors->toArray())
);
```

## Available builders

- `Schema::string()` — with modifiers such as `trimmed()`, `min()`, `max()`
- `Schema::int()` — with `min()` / `max()` bounds
- `Schema::bool()`
- `Schema::date()`
- `Schema::enumeration(['active', 'inactive'])`
- `Schema::arrayOf($itemSchema)` — validates every item, reporting nested paths
- `Schema::shape([...])` — object/associative-array validation
- `Schema::option($inner)` — accepts `null`, wrapping the value in an `Option`

## Error reporting

`ValidationErrorBag` collects every error with its path:

```php
$errors->count();
$errors->first();      // ?ValidationError
$errors->all();        // ValidationError[]
$errors->toArray();    // [['path' => 'age', 'message' => '...', 'code' => '...'], ...]
$errors->summary();    // "age: must be at least 18 (and 2 more errors)"
```

Nested structures report full paths (e.g. `items.2.email`), which makes API error responses precise.

## Reuse and immutability

Every modifier returns a new schema instance, so shared base schemas are safe:

```php
$email = Schema::string()->trimmed()->min(5);

$signup = Schema::shape(['email' => $email, 'password' => Schema::string()->min(8)]);
$invite = Schema::shape(['email' => $email]);
```
