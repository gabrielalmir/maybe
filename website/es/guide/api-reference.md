# Referencia de API

Firmas públicas actualizadas para **0.4.0**. Consulta las guías de cada módulo
para ejemplos y decisiones de diseño.

## Option y Result

- `Option::some($value)`, `Option::none()` y `Option::fromNullable($value)`.
- `map`, `flatMap`, `filter`, `match`, `unwrap`, `unwrapOr`, `unwrapOrElse` y `expect`.
- `Result::ok($value)` y `Result::err($error)`.
- `map`, `mapErr`, `andThen`, `orElse`, `match`, `unwrap`, `unwrapErr`, `okOption` y `errOption`.

## Schema y DTO

`Schema::string()`, `int()`, `bool()`, `date()`, `enumeration()`, `arrayOf()`,
`shape()` y `option()` crean validadores inmutables. Todos ofrecen
`safeParse()` y `parse()`.

`DTO::fromArray()` devuelve `Result<DTO, ValidationErrorBag>` y `DTO::parse()`
lanza `ValidationException` cuando falla.

## Async

```php
Async::run(callable $task, array $args = [], array $options = []): AsyncFuture
Async::all(array $futures): array
Async::race(array $futures)
Async::pool(array $tasks, int $limit = 5, array $options = []): array
```

Opciones: `timeout`, `poll_interval`, `temp_dir`, `php_binary`, `autoload`,
`max_input_bytes` (16 MiB), `max_output_bytes` (64 MiB) e
`include_remote_trace` (false). Los límites aceptan `null` para desactivarse.

Configuración global: `setDefaultTempDir`, `setDefaultTimeout`,
`setDefaultPollInterval`, `setDefaultMaxInputBytes` y
`setDefaultMaxOutputBytes`.

`PayloadTooLargeException` identifica la dirección del payload que excedió el
límite. `TaskFailedException::remoteTrace()` permanece vacío salvo que se
solicite explícitamente.

## Helpers

Los helpers auto-cargados incluyen `some`, `none`, `fromNullable`, `ok`, `err`,
los builders de Schema, `async` y `await`.
