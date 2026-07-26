# Tutorial: Cola de emails en CodeIgniter 3 y contratos en Laravel

Este tutorial construye dos flujos completos. Primero, una cola de emails en PHP 7.4 para una aplicación CodeIgniter 3, ejecutable también en Windows y con persistencia intercambiable en memoria o SQL Server. Después, los mismos límites funcionales se aplican a una plataforma de firma de contratos en Laravel con PHP 8+.

Los ejemplos usan las cinco primitivas de Maybe sin transformar la biblioteca en un framework:

- `Schema` valida datos en el límite;
- `DTO` transporta únicamente datos validados;
- `Result` convierte los fallos esperados en parte de la firma;
- `Option` representa una búsqueda que puede no encontrar un valor;
- `Async` paraleliza trabajo independiente y limitado por procesos.

> Los fragmentos son un punto de partida arquitectónico. Adapta los namespaces, la autenticación, el mailer y la observabilidad a tu proyecto. Las credenciales nunca deben formar parte del payload persistido.

## Parte 1 — `EmailQueueService` en CodeIgniter 3

### 1. Instalación y bootstrap

Instala Maybe en el directorio de la aplicación y habilita el autoload de Composer:

```bash
composer require gabrielalmir/maybe
```

```php
// application/config/config.php
$config['composer_autoload'] = FCPATH . 'vendor/autoload.php';
```

Mapea `App\` a `application/` en el `composer.json` de la aplicación. Todos los archivos de esta primera parte usan únicamente sintaxis compatible con PHP 7.4.

### 2. Contrato de persistencia

Un mensaje pasa por `pending`, `processing`, `sent` o `failed`. `deduplication_key` hace que `enqueue` sea idempotente; `attempts` y `available_at` permiten reintentos con backoff.

```sql
CREATE TABLE email_queue (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    deduplication_key VARCHAR(100) NOT NULL,
    recipient NVARCHAR(320) NOT NULL,
    subject NVARCHAR(200) NOT NULL,
    body NVARCHAR(MAX) NOT NULL,
    status VARCHAR(20) NOT NULL,
    attempts INT NOT NULL CONSTRAINT DF_email_queue_attempts DEFAULT 0,
    available_at DATETIME2 NOT NULL,
    claimed_at DATETIME2 NULL,
    sent_at DATETIME2 NULL,
    last_error NVARCHAR(2000) NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT DF_email_queue_created DEFAULT SYSUTCDATETIME(),
    CONSTRAINT UQ_email_queue_deduplication UNIQUE (deduplication_key),
    CONSTRAINT CK_email_queue_status CHECK (status IN ('pending', 'processing', 'sent', 'failed'))
);

CREATE INDEX IX_email_queue_claim
    ON email_queue (status, available_at, id);
```

Usa UTC en la base de datos y en el worker. En producción, define también una política para devolver elementos `processing` abandonados a `pending` cuando muera un proceso.

### 3. Validar la entrada con `Schema` y `DTO`

```php
<?php

declare(strict_types=1);

namespace App\Email;

use Maybe\DTO\DTO;
use Maybe\Schema\ObjectSchema;
use Maybe\Schema\Schema;

final class QueueEmailDTO extends DTO
{
    /** @var string */
    public $deduplicationKey;
    /** @var string */
    public $recipient;
    /** @var string */
    public $subject;
    /** @var string */
    public $body;

    private function __construct(string $key, string $recipient, string $subject, string $body)
    {
        $this->deduplicationKey = $key;
        $this->recipient = $recipient;
        $this->subject = $subject;
        $this->body = $body;
    }

    public static function schema(): ObjectSchema
    {
        return Schema::shape(array(
            'deduplication_key' => Schema::string()->trimmed()->min(1)->max(100),
            'recipient' => Schema::string()->trimmed()->max(320)
                ->regex('/^[^@\\s]+@[^@\\s]+\\.[^@\\s]+$/'),
            'subject' => Schema::string()->trimmed()->min(1)->max(200),
            'body' => Schema::string()->min(1),
        ));
    }

    protected static function fromValidated(array $validated)
    {
        return new self(
            $validated['deduplication_key'],
            $validated['recipient'],
            $validated['subject'],
            $validated['body']
        );
    }
}
```

`QueueEmailDTO::fromArray()` devuelve `Result<QueueEmailDTO, ValidationErrorBag>`: una entrada inválida no llega al repositorio y no requiere excepciones como control de flujo.

### 4. Crear el puerto de persistencia

El servicio depende de una interfaz, no del Query Builder de CI3. Así, la misma regla funciona en pruebas, comandos locales y SQL Server.

```php
<?php

declare(strict_types=1);

namespace App\Email;

use Maybe\Option\Option;
use Maybe\Result\Result;

interface EmailQueueRepository
{
    /** @return Result<Option<array<string,mixed>>,string> */
    public function findByDeduplicationKey(string $key): Result;

    /** @return Result<array<string,mixed>,string> */
    public function add(QueueEmailDTO $email): Result;

    /** @return Result<array<int,array<string,mixed>>,string> */
    public function claim(int $limit): Result;

    /** @return Result<bool,string> */
    public function markSent(int $id): Result;

    /** @return Result<bool,string> */
    public function reschedule(int $id, string $reason, int $delaySeconds): Result;
}
```

`Option` comunica “todavía no existe” sin confundir ausencia con un error de infraestructura. La indisponibilidad de la base de datos es un `Err`, nunca `None`.

### 5. Implementación en memoria

Esta implementación es útil para pruebas y ejecución en un único proceso. No se comparte entre requests, workers ni subprocesos de `Async`.

```php
final class InMemoryEmailQueueRepository implements EmailQueueRepository
{
    /** @var array<int,array<string,mixed>> */
    private $items = array();

    public function findByDeduplicationKey(string $key): Result
    {
        foreach ($this->items as $item) {
            if ($item['deduplication_key'] === $key) {
                return Result::ok(Option::some($item));
            }
        }

        return Result::ok(Option::none());
    }

    public function add(QueueEmailDTO $email): Result
    {
        $id = count($this->items) + 1;
        $this->items[$id] = array(
            'id' => $id,
            'deduplication_key' => $email->deduplicationKey,
            'recipient' => $email->recipient,
            'subject' => $email->subject,
            'body' => $email->body,
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => gmdate('Y-m-d H:i:s'),
        );

        return Result::ok($this->items[$id]);
    }

    public function claim(int $limit): Result
    {
        $claimed = array();
        foreach ($this->items as $id => $item) {
            if (count($claimed) >= $limit || $item['status'] !== 'pending') {
                continue;
            }

            $this->items[$id]['status'] = 'processing';
            $this->items[$id]['attempts']++;
            $claimed[] = $this->items[$id];
        }

        return Result::ok($claimed);
    }

    public function markSent(int $id): Result
    {
        if (!isset($this->items[$id])) {
            return Result::err('email_not_found');
        }

        $this->items[$id]['status'] = 'sent';

        return Result::ok(true);
    }

    public function reschedule(int $id, string $reason, int $delaySeconds): Result
    {
        if (!isset($this->items[$id])) {
            return Result::err('email_not_found');
        }

        $this->items[$id]['status'] = 'pending';
        $this->items[$id]['last_error'] = $reason;
        $this->items[$id]['available_at'] = gmdate('Y-m-d H:i:s', time() + $delaySeconds);

        return Result::ok(true);
    }
}
```

### 6. Implementación de SQL Server para CI3

Recibe la conexión `CI_DB_query_builder` mediante inyección. Convierte las excepciones y los retornos falsos en el límite a `Result`. La reclamación debe ser atómica para que dos workers no envíen el mismo email.

```php
final class SqlServerEmailQueueRepository implements EmailQueueRepository
{
    /** @var \CI_DB_query_builder */
    private $database;

    public function __construct(\CI_DB_query_builder $database)
    {
        $this->database = $database;
    }

    public function findByDeduplicationKey(string $key): Result
    {
        $row = $this->database->where('deduplication_key', $key)
            ->limit(1)
            ->get('email_queue')
            ->row_array();

        if ($this->database->error()['code'] !== 0) {
            return Result::err('email_queue_lookup_failed');
        }

        return Result::ok(Option::fromNullable($row ?: null));
    }

    public function add(QueueEmailDTO $email): Result
    {
        $saved = $this->database->insert('email_queue', array(
            'deduplication_key' => $email->deduplicationKey,
            'recipient' => $email->recipient,
            'subject' => $email->subject,
            'body' => $email->body,
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => gmdate('Y-m-d H:i:s'),
        ));

        if (!$saved) {
            return Result::err('email_queue_insert_failed');
        }

        return $this->findByDeduplicationKey($email->deduplicationKey)
            ->andThen(static function (Option $stored): Result {
                return $stored->okOr('email_queue_insert_not_found');
            });
    }

    public function claim(int $limit): Result
    {
        $sql = <<<'SQL'
;WITH candidates AS (
    SELECT TOP (?) *
    FROM email_queue WITH (UPDLOCK, READPAST, ROWLOCK)
    WHERE status = 'pending' AND available_at <= SYSUTCDATETIME()
    ORDER BY id
)
UPDATE candidates
SET status = 'processing', claimed_at = SYSUTCDATETIME(), attempts = attempts + 1
OUTPUT inserted.*;
SQL;
        $query = $this->database->query($sql, array($limit));

        if ($query === false) {
            return Result::err('email_queue_claim_failed');
        }

        return Result::ok($query->result_array());
    }

    public function markSent(int $id): Result
    {
        $saved = $this->database->where('id', $id)->update('email_queue', array(
            'status' => 'sent',
            'sent_at' => gmdate('Y-m-d H:i:s'),
            'last_error' => null,
        ));

        return $saved ? Result::ok(true) : Result::err('email_queue_update_failed');
    }

    public function reschedule(int $id, string $reason, int $delaySeconds): Result
    {
        $availableAt = gmdate('Y-m-d H:i:s', time() + $delaySeconds);
        $saved = $this->database->where('id', $id)->update('email_queue', array(
            'status' => 'pending',
            'available_at' => $availableAt,
            'last_error' => substr($reason, 0, 2000),
        ));

        return $saved ? Result::ok(true) : Result::err('email_queue_reschedule_failed');
    }
}
```

Los hints `UPDLOCK`, `READPAST` y `ROWLOCK` hacen que los workers concurrentes omitan las filas ya bloqueadas. Configura `db_debug` como `false` para que el adaptador pueda convertir los fallos del driver en `Err`. La restricción `UNIQUE` sigue siendo la autoridad ante carreras de idempotencia: en una implementación real, traduce la violación de clave única a la lectura del elemento existente. No concatenes `$limit` ni datos del usuario en el SQL.

### 7. Servicio idempotente con `Result` y `Option`

```php
final class EmailQueueService
{
    /** @var EmailQueueRepository */
    private $repository;

    public function __construct(EmailQueueRepository $repository)
    {
        $this->repository = $repository;
    }

    /** @return Result<array<string,mixed>,mixed> */
    public function enqueue(array $input): Result
    {
        return QueueEmailDTO::fromArray($input)
            ->andThen(function (QueueEmailDTO $email): Result {
                return $this->repository
                    ->findByDeduplicationKey($email->deduplicationKey)
                    ->andThen(function (Option $existing) use ($email): Result {
                        return $existing
                            ->map(static function (array $stored): Result {
                                return Result::ok($stored);
                            })
                            ->unwrapOrElse(function () use ($email): Result {
                                return $this->repository->add($email);
                            });
                    });
            });
    }
}
```

En el controller de CI3, compón las operaciones y convierte el `Result` exactamente una vez, en el límite HTTP:

```php
public function enqueue()
{
    $result = $this->email_queue_service->enqueue($this->input->post(null, true));

    return $result->match(
        function (array $email) {
            return $this->output->set_status_header(202)
                ->set_content_type('application/json')
                ->set_output(json_encode(array('data' => $email)));
        },
        function ($error) {
            $payload = method_exists($error, 'toArray') ? $error->toArray() : $error;

            return $this->output->set_status_header(422)
                ->set_content_type('application/json')
                ->set_output(json_encode(array('error' => $payload)));
        }
    );
}
```

Elige la persistencia en la composición de la aplicación, sin un `if` dentro de la regla de negocio:

```php
$repository = ENVIRONMENT === 'testing'
    ? new InMemoryEmailQueueRepository()
    : new SqlServerEmailQueueRepository($this->db);

$service = new EmailQueueService($repository);
```

### 8. Worker y `Async` compatibles con Windows

`Async` usa `proc_open`, `PHP_BINARY`, `DIRECTORY_SEPARATOR` y `NUL` en Windows. Verifica que `proc_open` esté habilitado y que el usuario del proceso pueda ejecutar PHP CLI y escribir en el directorio temporal.

Existe una separación importante:

1. el proceso padre reclama elementos en SQL Server;
2. cada subproceso recibe únicamente arrays serializables y crea su propia conexión de transporte;
3. el padre espera los resultados y actualiza la base de datos.

Nunca captures `$this->db`, una conexión SMTP ni el superobjeto de CI en una closure asíncrona. Los recursos y la memoria no se comparten entre procesos.

```php
use Maybe\Async\Async;

final class EmailQueueWorker
{
    /** @var EmailQueueRepository */
    private $repository;

    public function __construct(EmailQueueRepository $repository)
    {
        $this->repository = $repository;
    }

    public function runBatch(int $limit = 10): Result
    {
        return $this->repository->claim($limit)->andThen(function (array $emails): Result {
            $tasks = array();
            foreach ($emails as $email) {
                $tasks[$email['id']] = static function () use ($email): array {
                    // A classe deve ser carregável pelo Composer dentro do subprocesso.
                    $transport = TransportFactory::fromEnvironment();

                    return $transport->send($email);
                };
            }

            try {
                $outcomes = Async::pool($tasks, 4, array(
                    'timeout' => 30.0,
                    'temp_dir' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'maybe-email-queue',
                ));
            } catch (\Throwable $error) {
                return Result::err('email_batch_failed: ' . $error->getMessage());
            }

            return $this->persistOutcomes($emails, $outcomes);
        });
    }

    private function persistOutcomes(array $emails, array $outcomes): Result
    {
        foreach ($emails as $email) {
            $outcome = $outcomes[$email['id']];
            $saved = !empty($outcome['sent'])
                ? $this->repository->markSent((int) $email['id'])
                : $this->repository->reschedule(
                    (int) $email['id'],
                    (string) $outcome['error'],
                    min(3600, 30 * (2 ** (int) $email['attempts']))
                );

            if ($saved->isErr()) {
                return $saved;
            }
        }

        return Result::ok(count($outcomes));
    }
}
```

`Async::pool()` lanza una excepción si una tarea falla y cancela las restantes; por eso, el transporte debería devolver preferiblemente `array('sent' => false, 'error' => '...')` para fallos esperados. El límite de cuatro evita crear un proceso sin control por cada email.

En Windows, ejecuta un comando CLI de CI3 mediante el Programador de tareas, por ejemplo `C:\\php\\php.exe C:\\app\\index.php email_worker run`, con repetición y un usuario de servicio. En Linux, el mismo controller CLI puede ejecutarse con cron o un supervisor. No uses persistencia en memoria en este escenario: cada ejecución comenzaría vacía.

### 9. Checklist de producción

- mantén la clave única de idempotencia y gestiona su violación;
- recupera las reclamaciones abandonadas y define un máximo de intentos y una política dead-letter;
- registra `queue_id`, intento, duración y error sin contenido sensible del cuerpo;
- usa timeout tanto en `Async` como en el cliente SMTP/API;
- conserva el error original con un tamaño limitado;
- prueba ambos repositorios con el mismo contract test;
- usa SQL Server para múltiples procesos; la memoria es únicamente local;
- mantén los efectos externos fuera de transacciones largas de base de datos;
- realiza un cierre ordenado y deja el elemento recuperable después de una interrupción.

## Parte 2 — Plataforma de firma de contratos en Laravel (PHP 8+)

Ahora crearemos un ejemplo de firma recurrente de contratos. Laravel se encarga de HTTP, Eloquent, transacciones y colas; Maybe hace explícitas la validación, la ausencia y los errores de dominio. No reemplaces la cola durable de Laravel por subprocesos dentro de una request web.

### 1. Flujo y modelo de datos

El flujo es:

1. crear un contrato como `draft`;
2. localizar el plan de suscripción activo;
3. crear el cobro en el proveedor con una clave idempotente;
4. activar la suscripción y el contrato en la misma transacción local;
5. publicar un job para solicitar la firma electrónica;
6. encolar el email de invitación mediante el servicio de la primera parte o un adaptador de Laravel.

Tablas mínimas: `plans`, `contracts`, `subscriptions` y `signature_requests`. Usa índices únicos para `contracts.uuid`, `subscriptions.provider_reference` y la clave idempotente del proveedor.

### 2. DTO de creación

El código de la aplicación puede usar características de PHP 8+, aunque la API de Maybe sigue siendo compatible con 7.4.

```php
<?php

declare(strict_types=1);

namespace App\Contracts;

use Maybe\DTO\DTO;
use Maybe\Schema\ObjectSchema;
use Maybe\Schema\Schema;

final class CreateContractData extends DTO
{
    public function __construct(
        public string $title,
        public string $signerName,
        public string $signerEmail,
        public string $planCode,
    ) {}

    public static function schema(): ObjectSchema
    {
        return Schema::shape([
            'title' => Schema::string()->trimmed()->min(3)->max(200),
            'signer_name' => Schema::string()->trimmed()->min(2)->max(150),
            'signer_email' => Schema::string()->trimmed()->max(320)
                ->regex('/^[^@\\s]+@[^@\\s]+\\.[^@\\s]+$/'),
            'plan_code' => Schema::string()->trimmed()->min(1)->max(50),
        ]);
    }

    protected static function fromValidated(array $validated): static
    {
        return new static(
            $validated['title'],
            $validated['signer_name'],
            $validated['signer_email'],
            $validated['plan_code'],
        );
    }
}
```

### 3. Repositorio con `Option`

```php
use App\Models\Plan;
use Maybe\Option\Option;

final class EloquentPlanRepository
{
    /** @return Option<Plan> */
    public function activeByCode(string $code): Option
    {
        return Option::fromNullable(
            Plan::query()->where('code', $code)->where('active', true)->first()
        );
    }
}
```

El caso ausente se convierte en un error de dominio únicamente cuando el caso de uso requiere un plan:

```php
$planResult = $plans->activeByCode($data->planCode)
    ->okOr(['code' => 'plan_not_found', 'retryable' => false]);
```

### 4. Gateway externo con `Result`

```php
interface BillingGateway
{
    /** @return Result<array{reference:string},array{code:string,retryable:bool}> */
    public function subscribe(Plan $plan, string $email, string $idempotencyKey): Result;
}

final class HttpBillingGateway implements BillingGateway
{
    public function subscribe(Plan $plan, string $email, string $idempotencyKey): Result
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                ->post(config('services.billing.url') . '/subscriptions', [
                    'plan' => $plan->code,
                    'email' => $email,
                ])
                ->throw();

            return Result::ok(['reference' => $response->json('id')]);
        } catch (\Throwable $error) {
            report($error);

            return Result::err(['code' => 'billing_unavailable', 'retryable' => true]);
        }
    }
}
```

El gateway captura excepciones porque está en el límite de una API que las lanza. El resto del dominio recibe un error estructurado y decide si debe reintentar.

### 5. Caso de uso transaccional

```php
final class CreateContract
{
    public function __construct(
        private EloquentPlanRepository $plans,
        private BillingGateway $billing,
    ) {}

    /** @return Result<Contract,mixed> */
    public function execute(array $input): Result
    {
        return CreateContractData::fromArray($input)
            ->mapErr(static fn ($errors): array => [
                'code' => 'validation_failed',
                'retryable' => false,
                'details' => $errors->toArray(),
            ])
            ->andThen(fn (CreateContractData $data): Result =>
                $this->plans->activeByCode($data->planCode)
                    ->okOr(['code' => 'plan_not_found', 'retryable' => false])
                    ->andThen(fn (Plan $plan): Result => $this->create($data, $plan))
            );
    }

    private function create(CreateContractData $data, Plan $plan): Result
    {
        $uuid = (string) Str::uuid();

        return $this->billing
            ->subscribe($plan, $data->signerEmail, 'contract:' . $uuid)
            ->andThen(function (array $charge) use ($data, $plan, $uuid): Result {
                try {
                    $contract = DB::transaction(function () use ($data, $plan, $uuid, $charge) {
                        $contract = Contract::create([
                            'uuid' => $uuid,
                            'title' => $data->title,
                            'signer_name' => $data->signerName,
                            'signer_email' => $data->signerEmail,
                            'status' => 'awaiting_signature',
                        ]);

                        $contract->subscription()->create([
                            'plan_id' => $plan->id,
                            'provider_reference' => $charge['reference'],
                            'status' => 'active',
                        ]);

                        return $contract;
                    });
                } catch (\Throwable $error) {
                    report($error);

                    return Result::err(['code' => 'contract_persistence_failed', 'retryable' => true]);
                }

                RequestElectronicSignature::dispatch($contract->id)->afterCommit();

                return Result::ok($contract);
            });
    }
}
```

En producción, un cobro externo exitoso seguido de un fallo local requiere compensación o, preferiblemente, saga/outbox. Una transacción SQL no hace atómica la API del proveedor. La clave idempotente permite repetir el comando sin un nuevo cobro.

### 6. Controller como adaptador HTTP

```php
final class ContractController
{
    public function store(Request $request, CreateContract $useCase): JsonResponse
    {
        return $useCase->execute($request->all())->match(
            static fn (Contract $contract): JsonResponse => response()->json([
                'data' => ['uuid' => $contract->uuid, 'status' => $contract->status],
            ], 201),
            static function (array $error): JsonResponse {
                $status = $error['code'] === 'validation_failed' ? 422 : 409;

                return response()->json(['error' => $error], $status);
            },
        );
    }
}
```

### 7. Job de firma e invitación

El job puede llamar al proveedor de firma y transformar la respuesta en un `Result`. Después de persistir la solicitud, encola la invitación con una clave determinista:

```php
final class RequestElectronicSignature implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $contractId) {}

    public function handle(SignatureGateway $signatures, EmailQueueService $emails): void
    {
        $contract = Contract::query()->find($this->contractId);

        Option::fromNullable($contract)
            ->okOr(['code' => 'contract_not_found', 'retryable' => false])
            ->andThen(fn (Contract $item): Result => $signatures->request($item))
            ->andThen(function (array $signature) use ($contract, $emails): Result {
                $contract->signatureRequests()->updateOrCreate(
                    ['provider_reference' => $signature['reference']],
                    ['url' => $signature['url'], 'status' => 'pending'],
                );

                return $emails->enqueue([
                    'deduplication_key' => 'contract-signature:' . $contract->uuid,
                    'recipient' => $contract->signer_email,
                    'subject' => 'Assine o contrato ' . $contract->title,
                    'body' => 'Acesse: ' . $signature['url'],
                ]);
            })
            ->match(
                static function (): void {},
                function ($error): void {
                    $retryable = is_array($error) && !empty($error['retryable']);
                    if ($retryable) {
                        $this->release(60);

                        return;
                    }

                    $code = is_array($error) ? (string) $error['code'] : 'email_validation_failed';
                    $this->fail($code);
                },
            );
    }
}
```

Para un Laravel distribuido, implementa `EmailQueueRepository` con Eloquent/SQL Server o adapta `EmailQueueService` para publicar un job de Laravel. El contrato del servicio permanece igual.

### 8. Dónde usar `Async` en Laravel

Prefiere Laravel Queue para cobros, firmas y emails: es durable, observable y admite reintentos. `Maybe\Async` es apropiado dentro de un comando CLI para tareas independientes, puras o fáciles de repetir, como generar previews de páginas ya persistidas:

```php
$previews = Async::pool(
    collect($pages)->mapWithKeys(
        static fn (array $page): array => [
            $page['number'] => static fn (): array => PreviewRenderer::render($page),
        ]
    )->all(),
    4,
    ['timeout' => 20.0]
);
```

No pases models de Eloquent conectados, containers, conexiones PDO ni closures que capturen servicios. Pasa arrays serializables, recrea los recursos en el hijo y persiste en el proceso padre. En Windows se aplican los mismos requisitos de `proc_open` y PHP CLI de la primera parte.

## Estrategia de pruebas

1. prueba `QueueEmailDTO` con un destinatario inválido y límites de texto;
2. ejecuta el mismo contract test contra los repositorios en memoria y SQL Server;
3. demuestra que dos llamadas con la misma clave devuelven un único mensaje;
4. ejecuta dos workers concurrentes y confirma que un elemento se reclama una sola vez;
5. simula un timeout y un fallo transitorio para validar el backoff y dead-letter;
6. en Laravel, usa fakes de los gateways y `Queue::fake()`;
7. prueba por separado la compensación/outbox después del éxito remoto y el fallo local;
8. mantén una prueba de integración real para cada driver o servicio externo homologado.

El beneficio central no es encadenar métodos: es hacer explícito cada límite. `Schema` y `DTO` protegen la entrada, `Option` distingue la ausencia, `Result` distingue el éxito del fallo recuperable y `Async` queda limitado al trabajo verdaderamente independiente. El puerto de persistencia permite cambiar entre memoria, SQL Server y Eloquent sin reescribir la regla de negocio.
