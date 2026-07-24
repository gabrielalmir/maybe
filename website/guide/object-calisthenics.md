# Object Calisthenics with Maybe

[Object Calisthenics](https://williamdurand.fr/2013/06/03/object-calisthenics/) is a set of nine rules by Jeff Bay for writing code that stays readable as it grows. Several of them are hard to follow in plain PHP — until you have the right types. Maybe's primitives make most of them the *natural* way to write, not extra discipline you have to remember.

This guide goes rule by rule: what it asks for, and how `Option`, `Result`, `Schema` and `DTO` help you get there. The library itself follows these rules (see `src/`), so the code you copy from the docs already reads this way.

## 1. One level of indentation per method

Nested `if`/`foreach` is where methods become unreadable. `match()` collapses a two-branch decision into a single expression, and `andThen()` replaces a staircase of nested success checks.

```php
// Before — two levels, and growing
function price(?Order $order): int
{
    if ($order !== null) {
        if ($order->isPaid()) {
            return $order->total();
        }
    }
    return 0;
}

// After — one level
function price(Order $order): int
{
    return $order->paidTotal()->unwrapOr(0);
}
```

## 2. Don't use `else`

`else` almost always means "two things are happening here." `Option` and `Result` give you exactly two branches with names, handled in one place:

```php
$label = $user->match(
    fn (User $u): string => $u->name(),
    fn (): string => 'guest'
);
```

For validation, `andThen()` chains the happy path and short-circuits failures — no `else`, no early-return ladder.

## 3. Wrap all primitives and strings

A bare `string $email` or `array $payload` carries no rules. `Schema` parses untrusted input into validated data at the boundary, and a `DTO` gives that data a name and a type:

```php
final class SignupData extends DTO
{
    public static function schema(): ObjectSchema
    {
        return Schema::shape([
            'email' => Schema::string()->trimmed()->regex('/^[^@\s]+@[^@\s]+\.[^@\s]+$/'),
            'age' => Schema::int()->min(18),
        ]);
    }
    // ...
}

$result = SignupData::fromArray($request); // Result<SignupData, ValidationErrorBag>
```

Past the boundary you pass a `SignupData`, never a raw array.

## 4. First-class collections

A class that wraps an array should wrap *only* that array. Maybe's own `ValidationErrorBag` is the example: it holds the error list and nothing else, and it's iterable and countable instead of exposing the raw array.

```php
foreach ($errors as $error) {
    echo $error->describedAs();
}
```

## 5. One dot per line — with one deliberate exception

The rule targets the Law of Demeter: don't reach *through* one object to get at another (`$order->customer()->address()->zip()`). That's still worth avoiding.

But a **fluent Maybe chain is not that** — every `->map()->andThen()->match()` returns a new object of the *same* type, so you're never reaching through unrelated collaborators. Maybe deliberately embraces these chains; they're the readable core of the library, and this guide treats them as the sanctioned exception to rule 5.

```php
$invoice = loadUser($id)
    ->andThen(fn (User $u): Result => charge($u))
    ->map(fn (Charge $c): string => $c->reference())
    ->unwrapOr('unpaid');
```

## 6. Don't abbreviate

Nothing library-specific here, but expressive types remove the *pressure* to abbreviate: you write `unwrapOr('guest')`, not a terse null-coalescing trick that begs for a `// ...` comment.

## 7. Keep all entities small

Small types compose. Maybe pushes you toward many small, single-purpose objects — a schema per field, a DTO per boundary, a value object per concept — instead of one god-service with a hundred branches.

## 8. No more than two instance variables

This is the rule most code fails, and the fix is always the same: wrap related fields into a value object. Maybe's schema internals show the pattern — `StringSchema` holds a `TextLength` and a `TextFormat` (two objects) instead of four loose scalars; `DateSchema` holds a format and a `DateBounds`.

When your own class wants a third field, that's the signal two of them belong together.

## 9. No getters, no setters

"Tell, Don't Ask": objects should *do* things, not hand out their internals for someone else to inspect. `ValidationError` renders itself (`describedAs()`) and re-parents itself (`underField()`) — it never exposes `->path()` or `->message()`. The one accepted exception is a **serialization boundary**: `toArray()` exists precisely for turning an object into a plain array at the edge (a JSON response), and nowhere else.

```php
// Ask (avoid): pulling fields out to format them elsewhere
$line = $error->path() . ': ' . $error->message(); // no such getters

// Tell (do): the error knows how to describe itself
$line = $error->describedAs();

// Serialization boundary (fine): turning the bag into a response body
return json_encode(['errors' => $errors->toArray()]);
```

`Option::unwrap()` and `Result::unwrap()` are **not** getters in this sense — they're the intentional, checked core of those types (and you should usually prefer `match()`/`unwrapOr()` over them anyway).

## The two exceptions, stated plainly

Applying the rules strictly still leaves two places where a purist reading would be wrong for this domain:

- **Fluent chains (rule 5):** allowed and encouraged — same-type returns aren't a Demeter violation.
- **`toArray()` (rule 9):** the single sanctioned serialization boundary; every other read of internals is gone.

Everything else in `src/` follows the rules as written. If you're using an AI assistant to write code with Maybe, point it at [`llms.txt`](/llms.txt) — it encodes these same rules so generated code inherits them.
