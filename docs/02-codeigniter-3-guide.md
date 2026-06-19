# CodeIgniter 3 Guide

This guide shows how to use Maybe in a CodeIgniter 3 project where Composer is already loaded.

## Basic Setup Assumptions

- The application runs on PHP `>=7.4`.
- Composer autoload is enabled in CodeIgniter 3.
- Maybe is installed with Composer.
- DTO classes live in an application namespace or another Composer-loaded path.

Example Composer bootstrap in `application/config/config.php`:

```php
$config['composer_autoload'] = FCPATH . 'vendor/autoload.php';
```

## Validating Request Input with Schema

```php
use Maybe\Schema\Schema;

$schema = Schema::shape(array(
    'email' => Schema::string()->trimmed()->min(5),
    'status' => Schema::enumeration(array('active', 'inactive')),
));

$result = $schema->safeParse($this->input->post(null, true));
```

The controller reads request data, but the validation rules are reusable and testable.

## Creating a DTO from Request Data

```php
use Maybe\DTO\DTO;
use Maybe\Schema\ObjectSchema;
use Maybe\Schema\Schema;

final class CreateCustomerDTO extends DTO
{
    /** @var string */
    public $email;

    /** @var string */
    public $status;

    private function __construct(string $email, string $status)
    {
        $this->email = $email;
        $this->status = $status;
    }

    public static function schema(): ObjectSchema
    {
        return Schema::shape(array(
            'email' => Schema::string()->trimmed()->min(5),
            'status' => Schema::enumeration(array('active', 'inactive')),
        ));
    }

    protected static function fromValidated(array $validated)
    {
        return new self($validated['email'], $validated['status']);
    }
}
```

## Controller Using `DTO::fromArray()`

```php
final class Customers extends CI_Controller
{
    public function create()
    {
        $input = $this->input->post(null, true);
        $dtoResult = CreateCustomerDTO::fromArray($input);

        return $dtoResult->match(
            function (CreateCustomerDTO $dto) {
                return $this->customer_service->create($dto)->match(
                    function ($customer) {
                        return $this->output
                            ->set_content_type('application/json')
                            ->set_output(json_encode(array('data' => $customer)));
                    },
                    function ($error) {
                        return $this->output
                            ->set_status_header(422)
                            ->set_content_type('application/json')
                            ->set_output(json_encode(array('error' => $error)));
                    }
                );
            },
            function ($errors) {
                return $this->output
                    ->set_status_header(422)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array('errors' => $errors->toArray())));
            }
        );
    }
}
```

## Service Returning Result

```php
use Maybe\Result\Result;

final class CustomerService
{
    public function create(CreateCustomerDTO $dto): Result
    {
        if ($this->emailAlreadyExists($dto->email)) {
            return Result::err('customer_email_already_exists');
        }

        $customer = array('email' => $dto->email, 'status' => $dto->status);

        return Result::ok($customer);
    }
}
```

## Converting Result into an HTTP or View Response

```php
return $serviceResult->match(
    function ($customer) {
        return $this->load->view('customers/show', array('customer' => $customer), true);
    },
    function ($error) {
        return $this->load->view('customers/error', array('error' => $error), true);
    }
);
```

## Keep Controllers Thin

Controllers should collect request data, call DTOs and services, and translate results into responses. Validation rules and business rules should live in reusable classes.

## Keep DTOs Independent from the Framework

DTOs should not depend on:

- `$this->input`.
- Session state.
- Database connections.
- Framework services.
- Request globals such as `$_POST` or `$_GET`.

This keeps DTOs easy to test and safe to reuse from controllers, jobs, CLI scripts, and imports.
