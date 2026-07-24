# DTO

`DTO` mapeia input bruto (dados de request, linhas de CSV, payloads de fila) em objetos validados e imutáveis. Um schema, um DTO — e em vez de lançar exceção, retorna um [`Result`](/pt/guide/result).

## Definindo um DTO

```php
use Maybe\DTO\DTO;
use Maybe\Schema\ObjectSchema;
use Maybe\Schema\Schema;

final class CustomerDTO extends DTO
{
    /** @var string */
    public $email;

    private function __construct(string $email)
    {
        $this->email = $email;
    }

    public static function schema(): ObjectSchema
    {
        return Schema::shape([
            'email' => Schema::string()->trimmed()->min(5),
        ]);
    }

    protected static function fromValidated(array $validated)
    {
        return new self($validated['email']);
    }
}
```

## Usando

```php
$dtoResult = CustomerDTO::fromArray(['email' => 'ana@example.com']);

$response = $dtoResult->match(
    fn (CustomerDTO $dto) => $service->create($dto),
    fn ($errors) => respondWith422($errors->toArray())
);
```

Pontos de entrada:

- `DTO::fromArray($input)` retorna `Result<DTO, ValidationErrorBag>`
- `DTO::parse($input)` lança exceção em erro de validação (para fronteiras onde exceções são aceitáveis)

## Diretrizes de design

Mantenha DTOs independentes do framework. Eles **não** devem depender de:

- Objetos de request ou superglobais (`$_POST`, `$_GET`)
- Estado de sessão
- Conexões de banco de dados
- Serviços do framework

Isso mantém os DTOs fáceis de testar e seguros para reuso em controllers, jobs, scripts CLI e importações.
