# AGENTS.md

Guidance for AI coding agents (Claude Code, Cursor, Copilot, etc.) working in this repository. This is a library, not an application — every change here affects downstream consumers, so favor small, reversible edits over broad refactors.

## What this project is

`Maybe` is a small, dependency-light PHP library providing five primitives: `Option<T>`, `Result<T, E>`, `Schema`, `DTO`, `Async`. It targets PHP `>=7.4`, including Windows and legacy CodeIgniter 3 applications. See `README.md` and `docs/` for user-facing documentation, and `website/` for the published docs site (VitePress, EN + PT-BR).

## Hard constraints

- **PHP `>=7.4` syntax only.** No constructor property promotion, no enums, no named arguments, no readonly properties, no union return types beyond what 7.4 supports. Check `composer.json` (`require.php`) before assuming a newer feature is safe.
- **No new mandatory runtime dependencies.** `opis/closure` is the only one, required by `Async`. Anything else must be `suggest`-only or dev-only.
- **Preserve backward compatibility** of `Option`, `Result`, `Schema`, `DTO`, `Async` public APIs unless the task explicitly asks for a breaking change — and if so, call it out clearly and update `CHANGELOG.md`.
- **Immutability.** Schema modifiers (`min()`, `trimmed()`, etc.) and `ValidationErrorBag` methods return new instances; never mutate `$this` in place.
- **`Some` cannot hold `null`.** `Option::fromNullable(null)` and `Some::map()` returning `null` both collapse to `None`, never throw for that specific case.

## Where things live

| Path | Purpose |
| --- | --- |
| `src/Option/`, `src/Result/` | Core monadic types (`Some`/`None`, `Ok`/`Err`) |
| `src/Schema/` | Validation primitives (`StringSchema`, `IntSchema`, `ObjectSchema`, `ArraySchema`, ...) |
| `src/DTO/` | `DTO` base class bridging `Schema` and typed objects |
| `src/Async/`, `src/Async.php`, `src/Async_future.php` | Process-based concurrency (`proc_open`) + CI3 global aliases |
| `src/functions.php`, `src/async_helpers.php` | Auto-loaded global helper functions (`some()`, `ok()`, `async()`, ...) |
| `tests/` | Pest tests, mirrored by module (`tests/Option/`, `tests/Result/`, ...) |
| `tests/Async/run.php` | Standalone async test runner (`composer test:async`) |
| `examples/` | Runnable, self-contained PHP scripts demonstrating real usage |
| `docs/` | Long-form Markdown guides (adoption, migration, anti-patterns, async safety) |
| `website/` | VitePress docs site source (do not confuse with `docs/` — website content is adapted from it, not identical) |

## Making changes to `src/`

1. Read the existing sibling class first (e.g. before touching `Some.php`, read `None.php` and `Option.php`) — the three must stay behaviorally consistent.
2. Every new public method on `Option`/`Result` needs a mirrored implementation (or inherited default via `match()`) in **both** subclasses (`Some`/`None`, `Ok`/`Err`).
3. Add PHPDoc generics (`@template`, `@param T`, `@return Option<U>`) consistent with the surrounding code — PHPStan (level 6, see `phpstan.neon`) relies on these.
4. Add or update tests in `tests/<Module>/` using the existing Pest style (`it('description', function (): void { ... })`, static closures, `PHPUnit\Framework\Assert`).

## Testing

This environment likely does not have PHP installed — check with `which php` before assuming you can run these:

```bash
composer install
composer lint          # PHPStan level 6
composer test          # Pest 1.x suite
composer test:async    # standalone async runner (tests/Async/run.php)
```

If PHP isn't available, say so explicitly rather than claiming tests pass. Read the code path carefully and cross-check against existing tests/examples instead.

## The `tests/` CI gate

The `ci.yml` workflow requires a `test-change` label on any PR that modifies files under `tests/`. If your change touches tests, mention this in the PR description so the label gets applied — don't try to route around it.

## Website (`website/`)

- Built with VitePress; content is bilingual (`website/*.md` = English root, `website/pt/*.md` = Portuguese).
- Custom theme lives in `website/.vitepress/theme/` (`custom.css` for brand tokens, `components/` for the hero code panel, badges, footer).
- `--vp-c-text-1` / `--vp-c-text-2` and other brand tokens are overridden in **both** `:root` and `.dark` in `custom.css` — if you add a new CSS custom property override, redefine it for both, or a value can silently leak between light/dark mode (this caused a real bug once).
- Verify with `cd website && npm run docs:build` before considering a docs-site change done. If Node is unavailable, say so explicitly.
- Don't touch `docs/*.md` (the repo-root long-form guides) when the task is about the website, and vice versa — they're related but not the same content.

## Style

- `declare(strict_types=1);` at the top of every PHP file.
- `final class` unless the class is explicitly meant to be extended (e.g. `Option`, `Result`, `DTO`, `AbstractSchema` are abstract base classes; their concrete implementations are `final`).
- Static closures (`static function`, `static fn`) wherever `$this` isn't needed.
- No comments explaining *what* the code does — only *why*, when the reason isn't obvious from the code itself.

## Object Calisthenics standard

`src/` follows Object Calisthenics. When adding or changing code here, keep it that way:

1. **One level of indentation per method.** Extract a private method instead of nesting; a leaf schema's `parse()` delegates each check to a collaborator (see `StringSchema` → `TextLength`/`TextFormat`, `DateSchema` → `DateBounds`).
2. **No `else`.** Use guard clauses, early returns, or `match()` on Option/Result.
3. **Wrap primitives.** Group related scalars into a value object (see `Path`, `Reason`, `TextLength`, `TextFormat`, `DateBounds`).
4. **First-class collections.** A class wrapping an array holds nothing else (see `ValidationErrorBag`).
5. **No getters** on domain/value objects — they render themselves (`describedAs()`, `describe()`, `summary()`) or serialize (`toArray()` is the one accepted boundary). `Option::unwrap()`/`Result::unwrap()` are the intentional core API, not getters.
6. **At most two instance variables per class.** If a class needs a third scalar, wrap two of them into a value object first.
7. **Small entities**, **no abbreviations**.

Two deliberate, documented exceptions (do not "fix" these):
- The **fluent Maybe chain** (`->map()->andThen()->match()`) is allowed and encouraged — same-type returns are not a Law-of-Demeter violation, so the "one dot per line" rule does not apply to it.
- `toArray()` on value objects/collections is the accepted **serialization boundary**; it is the only place internals become a plain array.

There is no automated OC linter in CI yet — treat this section as the review checklist. If you add one, wire it into `composer lint` and keep it PHP 7.4-compatible.

## Don't

- Don't add a web framework dependency, an HTTP client, or a database driver — this library is intentionally framework-agnostic.
- Don't introduce PHP 8-only syntax anywhere reachable by the default `composer install` (PHP 7.4).
- Don't weaken or delete a test to make a change pass — fix the code, or explain in the PR why the test's expectation was wrong.
- Don't add Claude Code / AI-attribution content to commits, PR bodies, or code comments unless explicitly asked.
