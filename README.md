# Maybe (v0.2.0)

`Maybe` is a PHP library for explicit and predictable business logic.

It combines 5 main building blocks:

- `Option<T>`: safe flow for optional values
- `Result<T, E>`: typed success/error without exceptions as control flow
- `Schema`: immutable parsing and validation
- `DTO`: validated mapping for input objects
- `Async`: concurrent execution via processes (`proc_open`) focused on PHP 7.4 + Windows + CI3

## Requirements

- PHP `>= 7.4`
- Composer

## Installation

```bash
composer require gabrielalmir/maybe
```

## Dependencies

- Main runtime: no extra mandatory dependencies
- `Async` module: uses `opis/closure` for closure serialization

## API Overview

### Option

```php
use Maybe\Option\Option;

$name = Option::fromNullable($payload['name'] ?? null)
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

### Result

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

### Schema

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

Available builders:

- `Schema::string()`, `Schema::int()`, `Schema::bool()`, `Schema::date()`
- `Schema::enumeration([...])`
- `Schema::arrayOf(...)`
- `Schema::shape([...])`
- `Schema::option(...)`

### DTO

```php
use Maybe\DTO\DTO;
use Maybe\Schema\ObjectSchema;
use Maybe\Schema\Schema;

final class CustomerDTO extends DTO
{
    /** @var string */
    public $email;

    private function __construct(string $email)
    {
        $this->email = $email;
    }

    public static function schema(): ObjectSchema
    {
        return Schema::shape([
            'email' => Schema::string()->trimmed()->min(5),
        ]);
    }

    protected static function fromValidated(array $validated)
    {
        return new self($validated['email']);
    }
}

$dtoResult = CustomerDTO::fromArray(['email' => 'ana@example.com']);
```

Entry points:

- `DTO::fromArray($input)` returns `Result<DTO, ValidationErrorBag>`
- `DTO::parse($input)` throws an exception on validation error

### Async

```php
$result = await(async(static function (): int {
    usleep(100000);
    return 42;
}));
```

Features:

- `async(callable $task, array $args = [], array $options = [])`
- `await($futureOrArray)`
- `Async::all([...])`
- `Async::race([...])`
- `Async::pool($tasks, $limit)`
- `AsyncFuture::then()->catch()->finally()->resolve()`
- `pending()`, `cancel()`, per-task timeout (`['timeout' => 2.5]`)

## Functional Helpers

The following functions are auto-loaded:

- Option/Result: `some()`, `none()`, `fromNullable()`, `ok()`, `err()`
- Schema: `stringSchema()`, `intSchema()`, `boolSchema()`, `dateSchema()`, `enumSchema()`, `arraySchema()`, `objectSchema()`, `optionSchema()`
- Async: `async()`, `await()`

Global aliases are also available for CI3 compatibility:

- `Async`
- `Async_future`

## CodeIgniter 3

With Composer loaded in the project:

```php
$this->load->library('async');

$value = await(async(static function (): int {
    return 123;
}));
```

## Async Limitations

- Processes are isolated (no shared memory)
- Non-serializable resources must be recreated in the child process
- There is process spawn overhead per task

## Development

```bash
composer lint
composer test:async
```

> Note: the legacy `test` runner uses Pest 1.x. On very new PHP versions, prefer `test:async` to validate the async module.
