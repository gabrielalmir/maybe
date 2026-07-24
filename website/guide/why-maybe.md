# Why Maybe?

Maybe exists to make the two most common sources of runtime surprise in PHP — `null` and exceptions used as control flow — **visible in the type of a value**, so the compiler-in-your-head (and your reviewer) can see them.

## The cost of `null`

`null` is the value that fits everywhere and means nothing. A function returning `?User` forces every caller to remember a check that the type doesn't enforce:

```php
$user = $repo->find($id);
echo strtoupper($user->name()); // fine until $user is null in production
```

`Option<User>` makes absence a case you must handle, not one you can forget:

```php
$repo->find($id)
    ->map(fn (User $u) => strtoupper($u->name()))
    ->unwrapOr('unknown');
```

## The cost of exceptions as control flow

Exceptions are great for *unexpected* failures. Used for *expected* ones — a declined payment, an invalid coupon, a business rule — they turn ordinary outcomes into invisible jumps that don't show up in a signature:

```php
function charge(Order $o): string       // can this throw? you have to read the body
```

`Result<string, PaymentError>` puts the failure in the return type, and `match()` forces both paths to be handled at the boundary:

```php
charge($order)->match(
    fn (string $ref) => "paid: {$ref}",
    fn (PaymentError $e) => "declined: {$e->reason()}"
);
```

Exceptions still belong to genuinely unexpected failures (broken config, I/O outages). Maybe is for the *expected* ones.

## What Maybe gives you

Five building blocks that share one philosophy — make success, failure, and absence explicit and typed:

- **`Option`** — optional values without scattered null checks.
- **`Result`** — success/error as data, composed with `andThen`/`orElse`.
- **`Schema`** — parse untrusted input into validated data at the boundary.
- **`DTO`** — give validated data a name and a type.
- **`Async`** — process-based concurrency with no extensions required.

## How it compares

Maybe is not the only option, and it isn't trying to beat specialists at their own game. Its niche is **one integrated toolkit that runs where legacy PHP lives** — PHP 7.4, Windows, CodeIgniter 3.

| You want… | Specialist tools | Where Maybe fits |
| --- | --- | --- |
| Just `Option` | `phpoption/phpoption` | Maybe bundles it with Result/Schema/DTO under one style and package. |
| Just `Result` | `GrahamCampbell/Result-Type` | Same — plus `andThen`/`orElse`/`okOption` and a shared error philosophy. |
| Rich validation | `respect/validation`, `symfony/validator`, Zod (JS) | `Schema` is smaller and returns a `Result`; pair it with `andThen` for cross-field rules. |
| Heavy async | `spatie/async`, `amphp`, `reactphp` | `Async` targets PHP 7.4 + Windows with no `pcntl`/extensions; simpler, not a full event loop. |

**Maybe's edge:** the five blocks are designed to compose with each other (a `Schema` returns a `Result`, a `DTO` wraps a `Schema`, an `Option` converts to a `Result`), in a single dependency that installs on PHP 7.4.

## When *not* to use Maybe

Honesty first — reach for something else when:

- You're on modern PHP (8.1+) and already standardized on a mature specialist library you're happy with.
- You need a full async event loop with non-blocking I/O — use `amphp`/`reactphp`, not `Async`.
- You need a validation engine with a large built-in rule catalog and i18n messages out of the box — `symfony/validator` or `respect/validation` may fit better.
- Your team strongly prefers exceptions and won't adopt `Result` at boundaries — the value comes from consistent use.

If you're maintaining legacy PHP, want typed success/error paths, and value one small, framework-agnostic dependency, that's exactly what Maybe is for.

Next: the [Tutorial](/guide/tutorial) builds a real validated flow end to end, or jump to the [API Reference](/guide/api-reference).
