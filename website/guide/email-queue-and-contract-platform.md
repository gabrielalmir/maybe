# Tutorial: Email Queue in CodeIgniter 3 and contracts in Laravel

This tutorial builds two complete workflows. First, an email queue in PHP 7.4 for a CodeIgniter 3 application, also runnable on Windows, with interchangeable in-memory or SQL Server persistence. Then, the same functional boundaries are applied to a contract-signing platform in Laravel with PHP 8+.

The examples use all five Maybe primitives without turning the library into a framework:

- `Schema` validates data at the boundary;
- `DTO` carries only validated data;
- `Result` makes expected failures part of the signature;
- `Option` represents a lookup that might not find a value;
- `Async` parallelizes independent, process-bounded work.

> The snippets are an architectural starting point. Adapt namespaces, authentication, mailer, and observability to your project. Credentials must never be part of a persisted payload.

## Part 1 — `EmailQueueService` in CodeIgniter 3

### 1. Installation and bootstrap

Install Maybe in the application directory and enable Composer autoloading:

```bash
composer require gabrielalmir/maybe
```

```php
// application/config/config.php
$config['composer_autoload'] = FCPATH . 'vendor/autoload.php';
```

Map `App\` to `application/` in the application’s `composer.json`. Every file in this first part uses only PHP 7.4-compatible syntax.

### 2. Persistence contract

A message moves through `pending`, `processing`, `sent`, or `failed`. `deduplication_key` makes `enqueue` idempotent; `attempts` and `available_at` enable retries with backoff.

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

Use UTC in both the database and the worker. In production, also define a policy that returns abandoned `processing` items to `pending` when a process dies.

### 3. Validate input with `Schema` and `DTO`

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

`QueueEmailDTO::fromArray()` returns `Result<QueueEmailDTO, ValidationErrorBag>`: invalid input never reaches the repository and does not require exceptions for control flow.

### 4. Create the persistence port

The service depends on an interface, not on the CI3 Query Builder. This lets the same rule work in tests, local commands, and SQL Server.

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

`Option` communicates “does not exist yet” without confusing absence with an infrastructure error. Database unavailability is an `Err`, never `None`.

### 5. In-memory implementation

This implementation is useful for tests and single-process execution. It is not shared among requests, workers, or `Async` subprocesses.

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

### 6. SQL Server implementation for CI3

Receive the `CI_DB_query_builder` connection through dependency injection. Convert exceptions and false returns at the boundary into `Result`. Claiming must be atomic so two workers cannot send the same email.

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

The `UPDLOCK`, `READPAST`, and `ROWLOCK` hints make concurrent workers skip rows that are already locked. Set `db_debug` to `false` so the adapter can convert driver failures into `Err`. The `UNIQUE` constraint remains the authority against idempotency races: in a real implementation, translate a unique-key violation into a lookup of the existing item. Do not concatenate `$limit` or user data into SQL.

### 7. Idempotent service with `Result` and `Option`

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

In the CI3 controller, compose the operations and convert the `Result` exactly once, at the HTTP boundary:

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

Choose persistence in the application composition root, without an `if` inside the business rule:

```php
$repository = ENVIRONMENT === 'testing'
    ? new InMemoryEmailQueueRepository()
    : new SqlServerEmailQueueRepository($this->db);

$service = new EmailQueueService($repository);
```

### 8. Windows-compatible worker and `Async`

`Async` uses `proc_open`, `PHP_BINARY`, `DIRECTORY_SEPARATOR`, and `NUL` on Windows. Verify that `proc_open` is enabled and that the process user can execute PHP CLI and write to the temporary directory.

There is an important separation:

1. the parent process claims items in SQL Server;
2. each subprocess receives only serializable arrays and creates its own transport connection;
3. the parent waits for the results and updates the database.

Never capture `$this->db`, an SMTP connection, or the CI superobject in an asynchronous closure. Resources and memory are not shared between processes.

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

`Async::pool()` throws when a task fails with an exception and cancels the remaining tasks; therefore, the transport should preferably return `array('sent' => false, 'error' => '...')` for expected failures. The limit of four avoids creating an uncontrolled process for every email.

On Windows, run a CI3 CLI command through Task Scheduler, for example `C:\\php\\php.exe C:\\app\\index.php email_worker run`, with repetition and a service user. On Linux, the same CLI controller can be triggered by cron or a supervisor. Do not use in-memory persistence in this scenario: every execution would start empty.

### 9. Production checklist

- keep the unique idempotency key and handle its violation;
- recover abandoned claims and define maximum attempts and a dead-letter policy;
- record `queue_id`, attempt, duration, and error without sensitive body content;
- use a timeout in both `Async` and the SMTP/API client;
- preserve the original error with a size limit;
- test both repositories with the same contract test;
- use SQL Server for multiple processes; memory is local only;
- keep external effects outside long database transactions;
- shut down gracefully and leave the item recoverable after interruption.

## Part 2 — Contract-signing platform in Laravel (PHP 8+)

We will now create a recurring contract-signing example. Laravel handles HTTP, Eloquent, transactions, and queues; Maybe makes validation, absence, and domain errors explicit. Do not replace Laravel’s durable queue with subprocesses inside a web request.

### 1. Workflow and data model

The workflow is:

1. create a contract as `draft`;
2. find the active subscription plan;
3. create the provider charge with an idempotency key;
4. activate the subscription and contract in the same local transaction;
5. dispatch a job to request the electronic signature;
6. enqueue the invitation email through the service from part one or through a Laravel adapter.

Minimum tables: `plans`, `contracts`, `subscriptions`, and `signature_requests`. Use unique indexes for `contracts.uuid`, `subscriptions.provider_reference`, and the provider idempotency key.

### 2. Creation DTO

Application code may use PHP 8+ features, although the Maybe API remains compatible with 7.4.

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

### 3. Repository with `Option`

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

The absent case becomes a domain error only when the use case requires a plan:

```php
$planResult = $plans->activeByCode($data->planCode)
    ->okOr(['code' => 'plan_not_found', 'retryable' => false]);
```

### 4. External gateway with `Result`

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

The gateway catches exceptions because it sits at the boundary of an API that throws. The rest of the domain receives a structured error and decides whether to retry.

### 5. Transactional use case

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

In production, a successful external charge followed by a local failure requires compensation or, preferably, a saga/outbox. A SQL transaction does not make the provider API atomic. The idempotency key lets the command be repeated without a new charge.

### 6. Controller as an HTTP adapter

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

### 7. Signature and invitation job

The job can call the signature provider and transform the response into a `Result`. After persisting the request, it enqueues the invitation with a deterministic key:

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

For distributed Laravel deployments, implement `EmailQueueRepository` with Eloquent/SQL Server or adapt `EmailQueueService` to dispatch a Laravel job. The service contract stays the same.

### 8. Where to use `Async` in Laravel

Prefer Laravel Queue for billing, signatures, and email: it is durable, observable, and supports retries. `Maybe\Async` is appropriate inside a CLI command for independent, pure, or easily repeatable tasks, such as generating previews for already persisted pages:

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

Do not pass connected Eloquent models, containers, PDO connections, or closures that capture services. Pass serializable arrays, recreate resources in the child, and persist in the parent process. On Windows, the same `proc_open` and PHP CLI requirements from part one apply.

## Testing strategy

1. test `QueueEmailDTO` with an invalid recipient and text limits;
2. run the same contract test against the in-memory and SQL Server repositories;
3. prove that two calls with the same key return a single message;
4. run two concurrent workers and confirm that an item is claimed once;
5. simulate a timeout and transient failure to validate backoff and dead-letter handling;
6. in Laravel, use gateway fakes and `Queue::fake()`;
7. test compensation/outbox separately after remote success and local failure;
8. keep one real integration test for every approved external driver or service.

The main benefit is not chaining methods: it is making every boundary explicit. `Schema` and `DTO` protect input, `Option` distinguishes absence, `Result` distinguishes success from recoverable failure, and `Async` remains limited to truly independent work. The persistence port makes it possible to switch among memory, SQL Server, and Eloquent without rewriting the business rule.
