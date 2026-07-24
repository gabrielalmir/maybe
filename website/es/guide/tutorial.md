# Tutorial: validar y construir un DTO

Construiremos un flujo que recibe un email, lo valida y devuelve un objeto de
dominio sin mezclar datos sin confianza con datos validados.

```php
use Maybe\DTO\DTO;
use Maybe\Result\Result;
use Maybe\Schema\ObjectSchema;
use Maybe\Schema\Schema;

final class Customer extends DTO
{
    public string $email;

    public static function schema(): ObjectSchema
    {
        return Schema::shape([
            'email' => Schema::string()->trimmed()->regex('/^[^@]+@[^@]+$/'),
        ]);
    }

    protected static function fromValidated(array $validated)
    {
        $customer = new self();
        $customer->email = $validated['email'];
        return $customer;
    }
}

/** @var Result<Customer, \Maybe\Schema\ValidationErrorBag> $result */
$result = Customer::fromArray(['email' => 'ana@example.com']);
```

`fromArray()` nunca lanza por un input inválido: devuelve `Err` con un
`ValidationErrorBag`. Usa `parse()` cuando una entrada inválida debe detener el
flujo.

Continúa con [Schema](/es/guide/schema), [DTO](/es/guide/dto) y [Recetas](/es/guide/recipes).
