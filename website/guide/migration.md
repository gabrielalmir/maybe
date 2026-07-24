# Incremental Migration

You don't need a rewrite to adopt Maybe. Each block stands on its own, and they compose as you go.

## Recommended order

1. **Schema at the boundaries** — replace ad-hoc `isset`/`empty` validation with a schema at each input boundary (HTTP request, CSV import, queue payload). Nothing else changes yet.
2. **DTOs for the messy inputs** — where validated data crosses layers, wrap it in a DTO so the shape is guaranteed downstream.
3. **Result in services** — new/changed service methods return `Result` instead of throwing or returning `false`/`null`. Callers `match` at the edge.
4. **Option for nullable lookups** — repositories return `Option` instead of `null`, converted with `okOr()` when a typed error is needed.
5. **Async last** — only after the team is comfortable, and only for isolated, serializable workloads.

## Coexisting with exceptions

Maybe does not force an all-or-nothing style. At boundaries with exception-based code:

```php
use Maybe\Result\Result;

function tryCatch(callable $fn): Result
{
    try {
        return Result::ok($fn());
    } catch (\Throwable $e) {
        return Result::err($e);
    }
}
```

And in the opposite direction, `unwrap()`/`expect()` convert an `Err` back into an exception at the layer where exceptions are the convention.

## What to avoid

- Don't wrap *everything* in Option/Result on day one — start where errors actually hurt.
- Don't pass `Result` deep into layers that never inspect it; resolve it at the closest sensible boundary.
- Don't use `unwrap()` as a shortcut in production paths — prefer `match`, `unwrapOr()` or `expect()` with a meaningful message.

For deeper guidance, see the repository docs: [usage patterns](https://github.com/gabrielalmir/maybe/blob/main/docs/03-usage-patterns.md), [practical recipes](https://github.com/gabrielalmir/maybe/blob/main/docs/04-practical-recipes.md) and [anti-patterns](https://github.com/gabrielalmir/maybe/blob/main/docs/05-anti-patterns.md).
