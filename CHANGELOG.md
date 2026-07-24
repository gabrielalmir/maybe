# Changelog

## [0.4.0]

### Security and reliability

- Authenticated and bounded `Async` IPC with private temporary files and safe process reaping.
- Prevented stdout pipe deadlocks and released settled future callbacks and intermediate values.
- Reduced large invalid-schema validation from repeated quadratic error copying to one-pass collection.
- Added security audit coverage, dependency auditing, and PHP 7.4/8.2+ CI coverage.

## [0.3.0]

Object Calisthenics pass over the library, with a matching guide so code written *with* Maybe reads the same way.

### Changed (breaking)

- `ValidationError` and `ValidationErrorBag` no longer expose field getters; they follow "Tell, Don't Ask".
  - Removed: `ValidationError::path()`, `::message()`, `::code()`; `ValidationErrorBag::all()`, `::first()`.
  - `ValidationError` now offers `describedAs(): string`, `underField()`, `underIndex()`, and `toArray()`.
  - `ValidationErrorBag` is now a first-class collection: `Countable` and iterable (`foreach`), with `describe(): string[]` and `toArray()`. `count()`, `summary()`, `isEmpty()`, `withError()`, `merge()` are unchanged.
  - Build a custom error with a `Path`: `new ValidationError(Path::field('name'), 'message', 'code')` (the constructor takes a `Path`, not a string).

### Migration from 0.2.x

- `$errors->all()` → iterate the bag directly (`foreach ($errors as $error)`) or `$errors->toArray()`.
- `$error->path()` / `$error->message()` → `$error->describedAs()` (a `"path: message"` line), or `$errors->describe()` for all lines at once.
- `$errors->first()->code()` → `$errors->toArray()[0]['code']`.
- `new ValidationError('$.field', $msg, $code)` → `new ValidationError(Path::field('field'), $msg, $code)`.

### Added

- "Object Calisthenics with Maybe" guide (EN + PT) and Object Calisthenics rules in `llms.txt` / `AGENTS.md`.
- `Path`, `Reason`, `TextLength`, `TextFormat`, `DateBounds` value objects (internal building blocks; `Path` is public for building custom errors).

## [0.2.2]

### Added

- `Result::andThen()` and `Result::orElse()` for chaining fallible operations without manual `match`.
- `Result::unwrapOr()`, `Result::unwrapOrElse()`, and `Result::expect()` for ergonomic error recovery.
- `Result::okOption()` and `Result::errOption()` for converting Result to Option.
- `Option::filter()` for predicative filtering of values.
- `Option::unwrapOrElse()`, `Option::expect()`, `Option::okOr()`, and `Option::okOrElse()` for flexible unwrapping and Result conversion.

### Fixed

- `Some::map()` now returns `None` when the callback returns `null`, instead of throwing an exception (consistent with `Option::fromNullable` semantics).

### Changed

- Removed hardcoded `version` field from `composer.json` (version is now derived from git tags).

## [0.2.1]

### Added

- Corporate adoption documentation for legacy PHP and CodeIgniter 3 teams.
- Practical usage patterns, recipes, anti-patterns, migration guidance, and async safety documentation.
- Repository governance files for licensing, security reporting, contributing, and release history.

## [0.2.0]

### Added

- Option type for optional values.
- Result type for explicit success/error flows.
- Schema validation primitives.
- DTO support for validated object mapping.
- Async helpers for process-based concurrent execution.
- CodeIgniter 3 compatibility helpers.
