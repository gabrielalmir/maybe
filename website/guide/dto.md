# DTO

`DTO` maps raw input (request data, CSV rows, queue payloads) into validated, immutable objects. One schema, one DTO — and instead of throwing, it returns a [`Result`](/guide/result).

## Defining a DTO

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

## Using it

```php
$dtoResult = CustomerDTO::fromArray(['email' => 'ana@example.com']);

$response = $dtoResult->match(
    fn (CustomerDTO $dto) => $service->create($dto),
    fn ($errors) => respondWith422($errors->toArray())
);
```

Entry points:

- `DTO::fromArray($input)` returns `Result<DTO, ValidationErrorBag>`
- `DTO::parse($input)` throws an exception on validation error (for boundaries where exceptions are acceptable)

## Design guidelines

Keep DTOs independent from your framework. They should **not** depend on:

- Request objects or superglobals (`$_POST`, `$_GET`)
- Session state
- Database connections
- Framework services

This keeps DTOs easy to test and safe to reuse from controllers, jobs, CLI scripts and imports.
