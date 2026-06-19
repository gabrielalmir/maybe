# Contributing

Thank you for helping improve Maybe. This project is intended to remain small, predictable, and compatible with legacy PHP environments.

## Development Setup

Requirements:

- PHP `>=7.4`.
- Composer.

Install dependencies:

```bash
composer install
```

## Running Checks

Run static analysis:

```bash
composer lint
```

Run the test suite:

```bash
composer test
```

Run async-specific tests:

```bash
composer test:async
```

## Pull Request Expectations

Pull requests should:

- Explain the problem being solved.
- Keep changes focused and reviewable.
- Avoid public API changes unless clearly justified.
- Preserve Composer autoload behavior.
- Avoid mandatory framework-specific dependencies.
- Include tests or documentation updates when behavior changes.

## Documentation Expectations

Documentation should be practical, PHP 7.4-compatible, and friendly to legacy application teams. Examples should avoid PHP 8-only syntax and should not overpromise framework integration.

## Backward Compatibility

Maybe must continue to support PHP `>=7.4`. Changes should preserve CodeIgniter 3 compatibility and avoid breaking existing use of `Option`, `Result`, `Schema`, `DTO`, and `Async`.

## Test Changes

Test changes should be reviewed carefully. Avoid weakening tests simply to make a change pass. If a test must change, explain what behavior changed and why the new expectation is correct.
