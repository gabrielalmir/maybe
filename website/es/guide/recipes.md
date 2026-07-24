# Recetas

## Convertir excepciones heredadas en `Result`

```php
use Maybe\Result\Result;

function findCustomer(int $id): Result
{
    try {
        return Result::ok($repository->find($id));
    } catch (Throwable $e) {
        return Result::err($e->getMessage());
    }
}
```

## Validar y normalizar en una sola frontera

```php
$email = Schema::string()->trimmed()->max(255)->safeParse($input['email'] ?? null);
```

## Procesar tareas independientes

```php
$results = Async::pool($jobs, 4, ['timeout' => 10.0]);
```

La receta importante es mantener cada frontera explícita: valida input,
transforma con `map`, encadena fallos con `andThen` y decide el resultado con
`match`.
