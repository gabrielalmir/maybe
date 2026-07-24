# Option

`Option<T>` representa un valor presente (`Some`) o ausente (`None`) sin usar
`null` como contrato implícito.

```php
use Maybe\Option\Option;

$displayName = Option::fromNullable($user['name'] ?? null)
    ->map(static fn (string $name): string => trim($name))
    ->filter(static fn (string $name): bool => $name !== '')
    ->unwrapOr('Invitado');
```

Usa `flatMap()` para encadenar funciones que ya devuelven `Option`, `match()`
para consumir las dos variantes y `okOr()` para convertir ausencia en un
`Result` tipado.

Consulta también [Result](/es/guide/result) cuando la ausencia necesita una
causa concreta.
