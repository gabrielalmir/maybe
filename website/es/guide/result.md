# Result

`Result<T, E>` separa éxito (`Ok`) y fallo esperado (`Err`) en el tipo de
retorno.

```php
use Maybe\Result\Result;

$result = Result::ok($payload)
    ->andThen(static fn (array $data): Result => validate($data))
    ->map(static fn (array $data): array => normalize($data))
    ->orElse(static fn (string $error): Result => Result::ok(recover($error)));

$value = $result->match(
    static fn (array $data): array => $data,
    static fn (string $error): array => ['error' => $error],
);
```

`andThen()` corta la cadena al primer `Err`; `mapErr()` transforma la causa sin
ejecutar el camino de éxito. Reserva excepciones para fallos realmente
excepcionales.
