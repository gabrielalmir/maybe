# Tutorial: a validated "create customer" flow

This tutorial builds one real use case end to end — turning an untrusted request payload into a created customer — using `Schema`, `DTO`, and `Result` together. It's framework-agnostic and PHP 7.4-compatible. By the end you'll have a controller-shaped function that never trusts raw input and never uses exceptions for expected failures.

If you haven't installed Maybe yet, see [Getting Started](/guide/getting-started). The finished code mirrors [`examples/schema-dto.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/schema-dto.php).

## Step 1 — Describe the input with a Schema

Start at the boundary. Instead of reaching into `$_POST` and checking fields by hand, declare the shape once:

```php
use Maybe\Schema\Schema;

$customerSchema = Schema::shape([
    'name' => Schema::string()->trimmed()->min(2),
    'email' => Schema::string()->trimmed()->regex('/^[^@\s]+@[^@\s]+\.[^@\s]+$/'),
    'age' => Schema::int()->min(18),
]);
```

`safeParse` returns a `Result`: `Ok` with cleaned data (already trimmed), or `Err` with a `ValidationErrorBag`.

```php
$result = $customerSchema->safeParse($request);
```

## Step 2 — Give the data a type with a DTO

A `Result<array, …>` is fine, but downstream code deserves a named type, not a loose array. A DTO binds the schema to an immutable object:

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

## Step 3 — Put business rules in a service returning Result

Structural validation ("is this an int ≥ 18?") is the schema's job. *Business* rules ("is this email already taken?") aren't — they live in a service that returns a `Result`:

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

## Step 4 — Compose the pipeline with andThen

`andThen` chains the DTO step into the service step, short-circuiting if validation already failed. No nested `if`, no `try/catch`:

```php
$service = new CustomerService();

$outcome = CustomerData::fromArray($request)
    ->andThen(fn (CustomerData $data): Result => $service->create($data));
```

`$outcome` is now a `Result<array, mixed>` that captures every way this can end: invalid input (a `ValidationErrorBag`), a business rule failure (a string code), or success (the created customer).

## Step 5 — Render one response at the boundary

`match()` forces you to handle success and failure in one place. Validation errors render themselves with `describe()`; you never pull `path`/`message` out by hand:

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

Send `$response['status']` and `json_encode($response['body'])` from whatever framework you're in — the flow above doesn't depend on any of them.

## What you built

- Input is validated **at the boundary** and never trusted afterward.
- Validated data travels as a **typed `CustomerData`**, not a raw array.
- Every outcome — invalid input, business-rule failure, success — is a **value**, handled in one `match()`.
- No exceptions for expected failures; no `null`; no nested conditionals.

Next steps: browse the [Recipes](/guide/recipes) for more patterns, see [Case Studies](/guide/case-studies) for email/SAP/contract scenarios, or keep everything readable with [Object Calisthenics](/guide/object-calisthenics).
