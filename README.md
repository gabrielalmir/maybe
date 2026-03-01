# Maybe

`Maybe` is a lightweight PHP library with functional building blocks inspired by Rust:

- `Option<T>` for nullable-safe flows
- `Result<T, E>` for explicit success/error handling
- small immutable schemas for validation/parsing
- DTO helpers built on top of schemas

It is designed to keep business code predictable, explicit, and easy to compose.

## Requirements

- PHP `>= 7.4`

## Installation

```bash
composer require gabrielalmir/maybe
```

## Quick Start

```php
<?php

declare(strict_types=1);

use Maybe\Option\Option;
use Maybe\Result\Result;

$customerName = Option::fromNullable('  Ana Souza  ')
    ->map('trim')
    ->flatMap(static function (string $value): Option {
        return $value === '' ? Option::none() : Option::some($value);
    })
    ->unwrapOr('Cliente');

$authorizePayment = static function (int $amountInCents, string $method): Result {
    if ($method !== 'credit_card') {
        return Result::err('Forma de pagamento não suportada');
    }

    return Result::ok('pay_' . substr(sha1((string) $amountInCents), 0, 8));
};

echo $authorizePayment(12990, 'credit_card')->match(
    static fn (string $transactionId): string => $customerName . ' - pagamento: ' . $transactionId,
    static fn (string $error): string => 'Error: ' . $error
) . PHP_EOL;
```

## Option

Use `Option` when a value may be missing.

```php
use Maybe\Option\Option;

$username = Option::fromNullable($input['username'] ?? null)
    ->map('trim')
    ->flatMap(static function (string $value): Option {
        return $value === '' ? Option::none() : Option::some($value);
    })
    ->unwrapOr('guest');
```

Main methods:

- `map(callable $fn): Option`
- `flatMap(callable $fn): Option`
- `match(callable $onSome, callable $onNone)`
- `unwrap()`, `unwrapOr($default)`
- `isSome()`, `isNone()`

## Result

Use `Result` when an operation can fail and you want typed, explicit errors.

```php
use Maybe\Result\Result;

function loadUser(int $id): Result
{
    if ($id <= 0) {
        return Result::err('invalid_id');
    }

    return Result::ok(['id' => $id, 'name' => 'Ana']);
}

$message = loadUser(10)->match(
    static fn (array $user): string => 'User: ' . $user['name'],
    static fn (string $error): string => 'Error: ' . $error
);
```

Main methods:

- `map(callable $fn): Result`
- `mapErr(callable $fn): Result`
- `match(callable $onOk, callable $onErr)`
- `unwrap()`, `unwrapErr()`
- `isOk()`, `isErr()`

## Schemas

`Schema` lets you parse and validate input with immutable schema objects.

```php
use Maybe\Schema\Schema;
use Maybe\Schema\ValidationErrorBag;

$schema = Schema::shape([
    'email' => Schema::string()->trimmed()->min(5),
    'age' => Schema::int()->min(18),
]);

$result = $schema->safeParse([
    'email' => '  user@example.com  ',
    'age' => 23,
]);

echo $result->match(
    static fn (array $data): string => 'OK: ' . $data['email'],
    static fn (ValidationErrorBag $errors): string => 'ERR: ' . $errors->summary()
);
```

Available builders:

- `Schema::string()` (`trimmed()`, `min()`, `max()`, `regex()`)
- `Schema::int()` (`min()`, `max()`)
- `Schema::bool()`
- `Schema::arrayOf(...)`
- `Schema::shape([...])` (`allowUnknown()`)
- `Schema::option(...)` for nullable values into `Option<T>`

Error handling:

- `parse($input)` throws `ValidationException` on invalid input
- `safeParse($input)` returns `Result<T, ValidationErrorBag>`

## DTOs

Extend `Maybe\DTO\DTO` to validate raw payloads and create typed objects in one place.

```php
use Maybe\DTO\DTO;
use Maybe\Option\Option;
use Maybe\Schema\ObjectSchema;
use Maybe\Schema\Schema;

final class CustomerRegistrationDTO extends DTO
{
    /** @var string */
    public $fullName;

    /** @var string */
    public $email;

    /** @var int */
    public $birthYear;

    /** @var Option<string> */
    public $phoneNumber;

    private function __construct(string $fullName, string $email, int $birthYear, Option $phoneNumber)
    {
        $this->fullName = $fullName;
        $this->email = $email;
        $this->birthYear = $birthYear;
        $this->phoneNumber = $phoneNumber;
    }

    public static function schema(): ObjectSchema
    {
        return Schema::shape([
            'full_name' => Schema::string()->trimmed()->min(5),
            'email' => Schema::string()->trimmed()->regex('/^[^@\s]+@[^@\s]+\.[^@\s]+$/'),
            'birth_year' => Schema::int()->min(1900)->max(2010),
            'phone_number' => Schema::option(
                Schema::string()->trimmed()->regex('/^\+?[0-9]{10,15}$/')
            ),
        ]);
    }

    protected static function fromValidated(array $validated)
    {
        return new self(
            $validated['full_name'],
            $validated['email'],
            $validated['birth_year'],
            $validated['phone_number']
        );
    }
}
```

DTO entry points:

- `CustomerRegistrationDTO::fromArray($input)` -> `Result<CustomerRegistrationDTO, ValidationErrorBag>`
- `CustomerRegistrationDTO::parse($input)` -> throws on validation failure

## Functional Helpers

Global helper functions are autoloaded from `src/functions.php`:

- `some()`, `none()`, `fromNullable()`
- `ok()`, `err()`
- `stringSchema()`, `intSchema()`, `boolSchema()`
- `arraySchema()`, `objectSchema()`, `optionSchema()`

## Development

```bash
composer test
composer lint
composer qa
```

See runnable examples in:

- `examples/option-result.php`
- `examples/schema-dto.php`
- `examples/schema-array-object.php`
- `examples/helpers.php`
- `examples/schema-parse-throw.php`

## License

MIT
