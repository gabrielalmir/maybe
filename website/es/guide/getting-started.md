# Primeros pasos

Maybe ofrece primitives pequeños para que los estados importantes de una
aplicación PHP sean visibles en sus tipos: `Option`, `Result`, `Schema`, `DTO` y
`Async`.

## Instalación

```bash
composer require gabrielalmir/maybe
```

La versión actual es `0.4.0`. La biblioteca mantiene compatibilidad con PHP
7.4 y prueba también las ramas modernas de PHP 8.2+.

## Tu primer `Result`

```php
use Maybe\Result\Result;

$result = Result::ok(42)
    ->map(static fn (int $value): int => $value * 2);

echo $result->unwrap(); // 84
```

Usa `Result::err($error)` para representar un fallo esperado y `match()` para
resolver ambos caminos explícitamente.

## Siguiente paso

- [¿Por qué Maybe?](/es/guide/why-maybe)
- [Tutorial completo](/es/guide/tutorial)
- [Referencia de API](/es/guide/api-reference)
