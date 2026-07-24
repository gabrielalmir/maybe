# CodeIgniter 3

O Maybe foi projetado pensando em apps CodeIgniter 3 legados. Este guia mostra a integração recomendada.

## Setup

Habilite o autoload do Composer em `application/config/config.php`:

```php
$config['composer_autoload'] = FCPATH . 'vendor/autoload.php';
```

## Validando input de request

```php
use Maybe\Schema\Schema;

$schema = Schema::shape(array(
    'email' => Schema::string()->trimmed()->min(5),
    'status' => Schema::enumeration(array('active', 'inactive')),
));

$result = $schema->safeParse($this->input->post(null, true));
```

O controller lê os dados do request, mas as regras de validação são reutilizáveis e testáveis.

## Controller usando DTO + Result

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

## Async no CI3

Os aliases globais `Async` e `Async_future` e os helpers `async()`/`await()` estão disponíveis:

```php
$this->load->library('async');

$value = await(async(static function (): int {
    return 123;
}));
```

## Diretrizes

- **Mantenha controllers finos** — coletam dados do request, chamam DTOs e services, traduzem resultados em respostas.
- **Mantenha DTOs livres do framework** — nada de `$this->input`, sessão, conexões de BD ou superglobais dentro de DTOs.
