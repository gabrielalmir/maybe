# CodeIgniter 3

Maybe was designed with legacy CodeIgniter 3 apps in mind. This guide shows the recommended integration.

## Setup

Enable Composer autoload in `application/config/config.php`:

```php
$config['composer_autoload'] = FCPATH . 'vendor/autoload.php';
```

## Validating request input

```php
use Maybe\Schema\Schema;

$schema = Schema::shape(array(
    'email' => Schema::string()->trimmed()->min(5),
    'status' => Schema::enumeration(array('active', 'inactive')),
));

$result = $schema->safeParse($this->input->post(null, true));
```

The controller reads request data, but the validation rules are reusable and testable.

## Controller using DTO + Result

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
                            ->set_output(json_encode(array('error' => $error)));
                    }
                );
            },
            function ($errors) {
                return $this->output
                    ->set_status_header(422)
                    ->set_output(json_encode(array('errors' => $errors->toArray())));
            }
        );
    }
}
```

## Async in CI3

Global aliases `Async` and `Async_future` plus the `async()`/`await()` helpers are available:

```php
$this->load->library('async');

$value = await(async(static function (): int {
    return 123;
}));
```

## Guidelines

- **Keep controllers thin** — collect request data, call DTOs and services, translate results into responses.
- **Keep DTOs framework-free** — no `$this->input`, session, DB connections or superglobals inside DTOs.
