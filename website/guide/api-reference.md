# API Reference

Complete signatures for every public type, current as of **0.4.0**. For prose and examples, follow the per-module guides; this page is the quick lookup.

## Option&lt;T&gt;

`Maybe\Option\Option` — an optional value, either `Some` or `None`.

| Member | Signature | Description |
| --- | --- | --- |
| `Option::some` | `some($value): Option<T>` | Wrap a present value (never `null`). |
| `Option::none` | `none(): Option<mixed>` | The empty option. |
| `Option::fromNullable` | `fromNullable($value): Option<T>` | `null` → `None`, otherwise `Some`. |
| `map` | `map(callable $fn): Option` | Transform the value; a `null` result collapses to `None`. |
| `flatMap` | `flatMap(callable $fn): Option` | Chain an operation returning an `Option`. |
| `filter` | `filter(callable $predicate): Option` | Keep the value only if the predicate holds. |
| `match` | `match(callable $onSome, callable $onNone)` | Handle both branches, returns their result. |
| `unwrap` | `unwrap()` | The value, or throws `UnwrapNoneException` on `None`. |
| `unwrapOr` | `unwrapOr($default)` | The value, or an eager fallback. |
| `unwrapOrElse` | `unwrapOrElse(callable $fn)` | The value, or a lazy fallback. |
| `expect` | `expect(string $message)` | The value, or throws with your message. |
| `okOr` | `okOr($error): Result` | `Some`→`Ok`, `None`→`Err($error)`. |
| `okOrElse` | `okOrElse(callable $fn): Result` | Like `okOr`, error computed lazily. |
| `isSome` / `isNone` | `(): bool` | Inspect the variant. |

## Result&lt;T, E&gt;

`Maybe\Result\Result` — a success (`Ok`) or a typed failure (`Err`).

| Member | Signature | Description |
| --- | --- | --- |
| `Result::ok` | `ok($value): Result<T,mixed>` | A success. |
| `Result::err` | `err($error): Result<mixed,E>` | A typed failure. |
| `map` | `map(callable $fn): Result` | Transform the success value. |
| `mapErr` | `mapErr(callable $fn): Result` | Transform the error value. |
| `andThen` | `andThen(callable $fn): Result` | Chain a fallible op; short-circuits on `Err`. |
| `orElse` | `orElse(callable $fn): Result` | Recover from `Err`; passes `Ok` through. |
| `match` | `match(callable $onOk, callable $onErr)` | Handle both branches. |
| `unwrap` | `unwrap()` | The value, or throws `UnwrapErrException` on `Err`. |
| `unwrapErr` | `unwrapErr()` | The error, or throws `UnwrapOkException` on `Ok`. |
| `unwrapOr` | `unwrapOr($default)` | The value, or an eager fallback. |
| `unwrapOrElse` | `unwrapOrElse(callable $fn)` | The value, or a fallback computed from the error. |
| `expect` | `expect(string $message)` | The value, or throws with your message. |
| `okOption` | `okOption(): Option` | `Ok(v)`→`Some(v)`, `Err`→`None`. |
| `errOption` | `errOption(): Option` | `Err(e)`→`Some(e)`, `Ok`→`None`. |
| `isOk` / `isErr` | `(): bool` | Inspect the variant. |

## Schema

`Maybe\Schema\Schema` — builders for immutable validators. Every modifier returns a new instance.

| Builder | Signature |
| --- | --- |
| `Schema::string` | `string(): StringSchema` |
| `Schema::int` | `int(): IntSchema` |
| `Schema::bool` | `bool(): BoolSchema` |
| `Schema::date` | `date(): DateSchema` |
| `Schema::enumeration` | `enumeration(array $allowedValues): EnumSchema` |
| `Schema::arrayOf` | `arrayOf(SchemaInterface $itemSchema): ArraySchema` |
| `Schema::shape` | `shape(array $shape): ObjectSchema` |
| `Schema::option` | `option(SchemaInterface $inner): OptionSchema` |

**Modifiers:** `StringSchema`: `->trimmed()`, `->min(int)`, `->max(int)`, `->regex(string)`. `IntSchema`: `->min(int)`, `->max(int)`. `DateSchema`: `->format(string)`, `->min(DateTimeImmutable)`, `->max(DateTimeImmutable)`. `ObjectSchema`: `->allowUnknown()`.

**Entry points (all schemas):** `->safeParse($input): Result<T, ValidationErrorBag>` (never throws) and `->parse($input): T` (throws `ValidationException`). `->transform(callable): SchemaInterface`.

## Validation errors (0.4.0)

Tell-Don't-Ask value objects — there are **no** `path()`, `message()`, `code()`, `all()`, or `first()` getters.

`Maybe\Schema\ValidationErrorBag` — a first-class collection, `Countable` and iterable.

| Member | Signature | Description |
| --- | --- | --- |
| `count` | `count(): int` | Number of errors (`Countable`). |
| `isEmpty` | `isEmpty(): bool` | Whether there are no errors. |
| iteration | `foreach ($bag as $error)` | Yields `ValidationError` items. |
| `describe` | `describe(): string[]` | One `"path: message"` line per error. |
| `summary` | `summary(): string` | First line plus "(and N more…)". |
| `toArray` | `toArray(): array[]` | Serialization boundary: `['path','message','code']` rows. |
| `withError` / `merge` | `(...): self` | Immutable add / combine. |

`Maybe\Schema\ValidationError`: `describedAs(): string`, `underField(string): self`, `underIndex(int): self`, `toArray(): array{path,message,code}`. Build one with a `Path`: `new ValidationError(Path::field('email'), 'message', 'code')`.

`Maybe\Schema\Path`: `Path::root(): self`, `Path::field(string): self`, `->underField(string): self`, `->underIndex(int): self`, `->toString(): string`.

## DTO

`Maybe\DTO\DTO` — abstract base mapping validated input into a typed object.

| Member | Signature | Description |
| --- | --- | --- |
| `schema` | `abstract static schema(): ObjectSchema` | Define the shape. |
| `fromValidated` | `abstract static protected fromValidated(array): static` | Build the instance from validated data. |
| `fromArray` | `static fromArray(array $input): Result<static, ValidationErrorBag>` | Validate, never throws. |
| `parse` | `static parse(array $input): static` | Validate, throws on invalid input. |

## Async

`Maybe\Async\Async` and `AsyncFuture` — concurrency via child processes (`proc_open`).

| Member | Signature | Description |
| --- | --- | --- |
| `async` | `async(callable $task, array $args = [], array $options = []): AsyncFuture` | Start a task. `options: ['timeout' => 2.5]`. Wraps `Async::run`. |
| `await` | `await($futureOrArray)` | Resolve one future, or an array via `Async::all`. |
| `Async::run` | `run(callable $task, array $args = [], array $options = []): AsyncFuture` | Same as `async()`, called directly on the class. |
| `Async::all` | `all(array $futures): array` | Wait for all (keys preserved). |
| `Async::race` | `race(array $futures)` | First to finish wins. |
| `Async::pool` | `pool(array $tasks, int $limit = 5, array $options = []): array` | Bounded concurrency. |
| `Async::setDefaultTempDir` | `setDefaultTempDir(string $tempDir): void` | Override where worker temp files are written. |
| `Async::setDefaultTimeout` | `setDefaultTimeout(?float $seconds): void` | Default per-task timeout when `options['timeout']` is omitted. |
| `Async::setDefaultPollInterval` | `setDefaultPollInterval(int $microseconds): void` | Default polling interval used while waiting on a future. |
| `Async::setDefaultMaxInputBytes` | `setDefaultMaxInputBytes(?int $bytes): void` | Default serialized input limit; `null` disables it. |
| `Async::setDefaultMaxOutputBytes` | `setDefaultMaxOutputBytes(?int $bytes): void` | Default serialized output limit; `null` disables it. |
| `AsyncFuture` | `->then(fn)`, `->catch(fn)`, `->finally(fn)` | Register callbacks. |
| | `->resolve()` | Block until done (or timeout). |
| | `->pending(): bool`, `->cancel()` | Non-blocking check / kill the process. |

Async size options are `max_input_bytes` (16 MiB by default), `max_output_bytes`
(64 MiB by default), and `include_remote_trace` (disabled by default). A size
limit can be set to `null` for a trusted, explicitly bounded workload.

`Maybe\Async\Exception\PayloadTooLargeException` reports which IPC direction
exceeded its configured limit. `TaskFailedException::remoteTrace()` is empty by
default; enable `include_remote_trace` only in trusted diagnostics.

## Global helper functions

Auto-loaded (namespace `Maybe\`): `some()`, `none()`, `fromNullable()`, `ok()`, `err()`, `stringSchema()`, `intSchema()`, `boolSchema()`, `dateSchema()`, `enumSchema()`, `arraySchema()`, `objectSchema()`, `optionSchema()`, `async()`, `await()`.

> Writing code with an AI assistant? Point it at [`llms.txt`](/llms.txt) for the same signatures in an LLM-friendly form.
