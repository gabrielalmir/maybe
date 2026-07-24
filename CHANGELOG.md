# Changelog

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
