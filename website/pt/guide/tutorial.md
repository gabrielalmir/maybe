# Tutorial: um fluxo validado de "cadastrar cliente"

Este tutorial constrói um caso de uso real de ponta a ponta — transformar um payload de request não confiável em um cliente cadastrado — usando `Schema`, `DTO` e `Result` juntos. É agnóstico de framework e compatível com PHP 7.4. Ao final você terá uma função no formato de controller que nunca confia em input cru e nunca usa exceções para falhas esperadas.

Se ainda não instalou o Maybe, veja [Primeiros Passos](/pt/guide/getting-started). O código final espelha [`examples/schema-dto.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/schema-dto.php).

## Passo 1 — Descreva o input com um Schema

Comece na fronteira. Em vez de mexer no `$_POST` e checar campos na mão, declare a forma uma vez:

```php
use Maybe\Schema\Schema;

$customerSchema = Schema::shape([
    'name' => Schema::string()->trimmed()->min(2),
    'email' => Schema::string()->trimmed()->regex('/^[^@\s]+@[^@\s]+\.[^@\s]+$/'),
    'age' => Schema::int()->min(18),
]);
```

`safeParse` retorna um `Result`: `Ok` com o dado limpo (já com trim), ou `Err` com um `ValidationErrorBag`.

```php
$result = $customerSchema->safeParse($request);
```

## Passo 2 — Dê um tipo ao dado com um DTO

Um `Result<array, …>` é ok, mas o código adiante merece um tipo com nome, não um array solto. Um DTO liga o schema a um objeto imutável:

```php
use Maybe\DTO\DTO;
use Maybe\Schema\ObjectSchema;
use Maybe\Schema\Schema;

final class CustomerData extends DTO
{
    /** @var string */
    public $name;
    /** @var string */
    public $email;
    /** @var int */
    public $age;

    private function __construct(string $name, string $email, int $age)
    {
        $this->name = $name;
        $this->email = $email;
        $this->age = $age;
    }

    public static function schema(): ObjectSchema
    {
        return Schema::shape([
            'name' => Schema::string()->trimmed()->min(2),
            'email' => Schema::string()->trimmed()->regex('/^[^@\s]+@[^@\s]+\.[^@\s]+$/'),
            'age' => Schema::int()->min(18),
        ]);
    }

    protected static function fromValidated(array $validated)
    {
        return new self($validated['name'], $validated['email'], $validated['age']);
    }
}

$dtoResult = CustomerData::fromArray($request); // Result<CustomerData, ValidationErrorBag>
```

## Passo 3 — Coloque regras de negócio num service que retorna Result

Validação estrutural ("isto é um int ≥ 18?") é trabalho do schema. Regras *de negócio* ("este email já existe?") não são — elas vivem num service que retorna um `Result`:

```php
use Maybe\Result\Result;

final class CustomerService
{
    public function create(CustomerData $data): Result
    {
        if ($this->emailExists($data->email)) {
            return Result::err('email_already_registered');
        }

        return Result::ok(['id' => 42, 'email' => $data->email]);
    }

    private function emailExists(string $email): bool
    {
        return $email === 'taken@example.com';
    }
}
```

## Passo 4 — Componha o pipeline com andThen

`andThen` encadeia o passo do DTO no passo do service, com curto-circuito se a validação já falhou. Sem `if` aninhado, sem `try/catch`:

```php
$service = new CustomerService();

$outcome = CustomerData::fromArray($request)
    ->andThen(fn (CustomerData $data): Result => $service->create($data));
```

`$outcome` agora é um `Result<array, mixed>` que captura toda forma de terminar: input inválido (um `ValidationErrorBag`), falha de regra de negócio (um código string), ou sucesso (o cliente criado).

## Passo 5 — Renderize uma resposta na fronteira

`match()` obriga a tratar sucesso e falha em um só lugar. Erros de validação se renderizam com `describe()`; você nunca puxa `path`/`message` na mão:

```php
$response = $outcome->match(
    function (array $customer): array {
        return ['status' => 201, 'body' => $customer];
    },
    function ($error): array {
        if ($error instanceof \Maybe\Schema\ValidationErrorBag) {
            return ['status' => 422, 'body' => ['errors' => $error->describe()]];
        }

        return ['status' => 409, 'body' => ['error' => $error]];
    }
);
```

Envie `$response['status']` e `json_encode($response['body'])` a partir de qualquer framework — o fluxo acima não depende de nenhum deles.

## O que você construiu

- O input é validado **na fronteira** e nunca mais é confiado.
- O dado validado trafega como um **`CustomerData` tipado**, não um array cru.
- Todo desfecho — input inválido, falha de regra de negócio, sucesso — é um **valor**, tratado em um `match()`.
- Sem exceções para falhas esperadas; sem `null`; sem condicionais aninhadas.

Próximos passos: veja as [Receitas](/pt/guide/recipes) para mais padrões, os [Estudos de Caso](/pt/guide/case-studies) para cenários de email/SAP/contrato, ou mantenha tudo legível com [Object Calisthenics](/pt/guide/object-calisthenics).
