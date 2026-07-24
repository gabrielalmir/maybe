# Schema

Los schemas son validadores inmutables. Cada modificador devuelve una nueva
instancia y no cambia el schema original.

```php
use Maybe\Schema\Schema;

$orderSchema = Schema::shape([
    'id' => Schema::int()->min(1),
    'email' => Schema::string()->trimmed()->max(255),
    'status' => Schema::enumeration(['draft', 'paid']),
]);

$result = $orderSchema->safeParse($input);
```

Usa `safeParse()` para obtener `Result<T, ValidationErrorBag>` sin lanzar, o
`parse()` cuando la entrada inválida debe lanzar `ValidationException`.

Builders disponibles: `string`, `int`, `bool`, `date`, `enumeration`, `arrayOf`,
`shape` y `option`. Los strings permiten `trimmed`, `min`, `max` y `regex`.
