# DTO

`DTO` combina un `ObjectSchema` con un constructor a partir de datos ya
validados.

```php
final class Signup extends \Maybe\DTO\DTO
{
    public string $email;

    public static function schema(): \Maybe\Schema\ObjectSchema
    {
        return \Maybe\Schema\Schema::shape([
            'email' => \Maybe\Schema\Schema::string()->trimmed(),
        ]);
    }

    protected static function fromValidated(array $validated)
    {
        $dto = new self();
        $dto->email = $validated['email'];
        return $dto;
    }
}
```

`Signup::fromArray($input)` devuelve `Result<Signup, ValidationErrorBag>`;
`Signup::parse($input)` devuelve el DTO o lanza por datos inválidos. Mantén
`fromValidated()` como una frontera: solo recibe datos que ya pasaron el schema.
