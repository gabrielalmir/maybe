# Getting Started

`Maybe` is a PHP library for explicit and predictable business logic. It combines five building blocks:

- **`Option<T>`** — safe flow for optional values
- **`Result<T, E>`** — typed success/error without exceptions as control flow
- **`Schema`** — immutable parsing and validation
- **`DTO`** — validated mapping for input objects
- **`Async`** — concurrent execution via processes (`proc_open`), focused on PHP 7.4 + Windows + CodeIgniter 3

## Requirements

- PHP `>= 7.4`
- Composer

## Installation

```bash
composer require gabrielalmir/maybe
```

## First steps

If you work in a corporate or legacy environment, start with `Schema`, `DTO` and `Result` before adopting `Async`:

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

$result->match(
    fn (array $data) => saveUser($data),
    fn ($errors) => respondWithErrors($errors->toArray())
);
```

## Functional helpers

The following namespaced functions are auto-loaded:

- Option/Result: `some()`, `none()`, `fromNullable()`, `ok()`, `err()`
- Schema: `stringSchema()`, `intSchema()`, `boolSchema()`, `dateSchema()`, `enumSchema()`, `arraySchema()`, `objectSchema()`, `optionSchema()`
- Async: `async()`, `await()`

## Where to go next

- [**Tutorial**](/guide/tutorial) — build a validated create-customer flow end to end
- [Why Maybe?](/guide/why-maybe) — the case against `null` and exceptions, and how Maybe compares
- [API Reference](/guide/api-reference) — every signature in one place
- [Option](/guide/option) — optional values without null checks
- [Result](/guide/result) — error handling as data
- [Schema](/guide/schema) — validation and parsing
- [DTO](/guide/dto) — validated input objects
- [Async](/guide/async) — process-based concurrency
- [CodeIgniter 3](/guide/codeigniter-3) — integrating with legacy CI3 apps
- [Incremental Migration](/guide/migration) — adopting Maybe in an existing codebase
