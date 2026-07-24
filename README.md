# Maybe (v0.4.0)

📖 **Documentation:** https://gabrielalmir.github.io/maybe/ — [Why Maybe?](https://gabrielalmir.github.io/maybe/guide/why-maybe) · [Tutorial](https://gabrielalmir.github.io/maybe/guide/tutorial) · [API Reference](https://gabrielalmir.github.io/maybe/guide/api-reference) · [Español](https://gabrielalmir.github.io/maybe/es/)

`Maybe` is a PHP library for explicit and predictable business logic.

It combines 5 main building blocks:

- `Option<T>`: safe flow for optional values
- `Result<T, E>`: typed success/error without exceptions as control flow
- `Schema`: immutable parsing and validation
- `DTO`: validated mapping for input objects
- `Async`: concurrent execution via processes (`proc_open`) focused on PHP 7.4 + Windows + CI3


## Adoption Guides

New users in corporate or legacy environments should start with `Schema`, `DTO`, and `Result` before adopting `Async`. Detailed adoption guidance lives in `docs/` so this README can remain a compact API overview.

- [Corporate Adoption Guide](docs/01-corporate-adoption-guide.md)
- [CodeIgniter 3 Guide](docs/02-codeigniter-3-guide.md)
- [Usage Patterns](docs/03-usage-patterns.md)
- [Practical Recipes](docs/04-practical-recipes.md)
- [Anti-Patterns](docs/05-anti-patterns.md)
- [Incremental Migration](docs/06-incremental-migration.md)
- [Async Safety Guide](docs/07-async-safety-guide.md)

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
- `filter(callable $predicate): Option`
- `match(callable $onSome, callable $onNone)`
- `unwrap()`, `unwrapOr($default)`, `unwrapOrElse(callable)`, `expect(string)`
- `okOr($error): Result`, `okOrElse(callable): Result`
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
- `andThen(callable $fn): Result`
- `orElse(callable $fn): Result`
- `match(callable $onOk, callable $onErr)`
- `unwrap()`, `unwrapErr()`, `unwrapOr($default)`, `unwrapOrElse(callable)`, `expect(string)`
- `okOption(): Option`, `errOption(): Option`
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
- authenticated IPC with default input/output limits of 16 MiB / 64 MiB
- `max_input_bytes`, `max_output_bytes`, and `include_remote_trace` options

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
- Task stdout/stderr is discarded to prevent pipe back-pressure deadlocks
- `Async` is not a same-user sandbox; callables and process configuration are trusted inputs

## Development

```bash
composer lint
composer test:async
```

> Note: the legacy `test` runner uses Pest 1.x. On very new PHP versions, prefer `test:async` to validate the async module.

## Examples

Run any example directly:

```bash
php examples/option-result.php              # Option + Result checkout flow
php examples/schema-dto.php                  # DTO with Schema validation
php examples/recipe-repository-lookup.php    # Option-based repository lookup
php examples/recipe-safe-external-call.php   # wrapping legacy exceptions into Result
php examples/recipe-batch-import.php         # batch validation with per-row errors
php examples/scenario-transactional-email.php     # order-confirmation email with SMTP fallback
php examples/scenario-sap-order-integration.php    # pushing orders into SAP with retryable vs business errors
php examples/scenario-contract-validation.php      # contract validation with cross-field business rules
php examples/async-basic.php
php examples/async-all-race.php
php examples/async-pool.php
php examples/async-chain-timeout-cancel.php
```

More recipes with explanations: [Recipes guide](https://gabrielalmir.github.io/maybe/guide/recipes). For full business context on the scenario examples: [Case Studies](https://gabrielalmir.github.io/maybe/guide/case-studies).

## Using an AI Coding Assistant

- [`AGENTS.md`](AGENTS.md) — conventions and constraints for AI agents contributing to this repository.
- [`llms.txt`](https://gabrielalmir.github.io/maybe/llms.txt) — a condensed, exact API reference for AI assistants generating code that *uses* this library.
