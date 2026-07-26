# Tutorial: Email Queue no CodeIgniter 3 e contratos no Laravel

Este tutorial constrói dois fluxos completos. Primeiro, uma fila de e-mails em PHP 7.4 para uma aplicação CodeIgniter 3, executável também no Windows e com persistência intercambiável em memória ou SQL Server. Depois, os mesmos limites funcionais são aplicados a uma plataforma de assinatura de contratos em Laravel com PHP 8+.

Os exemplos usam as cinco primitivas do Maybe sem transformar a biblioteca em framework:

- `Schema` valida dados na borda;
- `DTO` transporta somente dados validados;
- `Result` torna falhas esperadas parte da assinatura;
- `Option` representa uma busca que pode não encontrar valor;
- `Async` paraleliza trabalho independente e limitado por processos.

> Os trechos são um ponto de partida arquitetural. Adapte namespaces, autenticação, mailer e observabilidade ao projeto. Credenciais nunca devem fazer parte do payload persistido.

## Parte 1 — `EmailQueueService` no CodeIgniter 3

### 1. Instalação e bootstrap

Instale o Maybe no diretório da aplicação e habilite o autoload do Composer:

```bash
composer require gabrielalmir/maybe
```

```php
// application/config/config.php
$config['composer_autoload'] = FCPATH . 'vendor/autoload.php';
```

Mapeie `App\\` para `application/` no `composer.json` da aplicação. Todos os arquivos desta primeira parte usam apenas sintaxe compatível com PHP 7.4.

### 2. Contrato persistido

Uma mensagem passa por `pending`, `processing`, `sent` ou `failed`. `deduplication_key` torna o `enqueue` idempotente; `attempts` e `available_at` permitem retry com backoff.

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

Use UTC no banco e no worker. Em produção, defina também uma política para devolver itens `processing` abandonados a `pending` quando um processo morrer.

### 3. Validar a entrada com `Schema` e `DTO`

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

`QueueEmailDTO::fromArray()` retorna `Result<QueueEmailDTO, ValidationErrorBag>`: entrada inválida não chega ao repositório e não exige exceção como controle de fluxo.

### 4. Criar a porta de persistência

O serviço depende de uma interface, e não do Query Builder do CI3. Assim a mesma regra funciona em testes, comandos locais e SQL Server.

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

`Option` comunica “não existe ainda” sem confundir ausência com erro de infraestrutura. Já indisponibilidade do banco é `Err`, nunca `None`.

### 5. Implementação em memória

Esta implementação é útil para testes e execução em um único processo. Ela não é compartilhada entre requests, workers ou subprocessos do `Async`.

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

### 6. Implementação SQL Server para CI3

Receba a conexão `CI_DB_query_builder` por injeção. Converta exceções/retornos falsos na borda para `Result`. A reivindicação precisa ser atômica para dois workers não enviarem o mesmo e-mail.

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

Os hints `UPDLOCK`, `READPAST` e `ROWLOCK` fazem workers concorrentes pularem linhas já bloqueadas. Configure `db_debug` como `false` para o adapter poder converter falhas do driver em `Err`. A restrição `UNIQUE` continua sendo a autoridade contra corrida de idempotência: em uma implementação real, traduza a violação de chave única para a leitura do item existente. Não concatene `$limit` ou dados do usuário no SQL.

### 7. Serviço idempotente com `Result` e `Option`

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

No controller do CI3, faça a composição e converta o `Result` exatamente uma vez, na borda HTTP:

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

Escolha a persistência na composição da aplicação, sem `if` dentro da regra de negócio:

```php
$repository = ENVIRONMENT === 'testing'
    ? new InMemoryEmailQueueRepository()
    : new SqlServerEmailQueueRepository($this->db);

$service = new EmailQueueService($repository);
```

### 8. Worker e `Async` compatível com Windows

O `Async` usa `proc_open`, `PHP_BINARY`, `DIRECTORY_SEPARATOR` e `NUL` no Windows. Verifique que `proc_open` está habilitado e que o usuário do processo pode executar o PHP CLI e escrever no diretório temporário.

Há uma separação importante:

1. o processo pai reivindica itens no SQL Server;
2. cada subprocesso recebe somente arrays serializáveis e cria sua própria conexão de transporte;
3. o pai aguarda os resultados e atualiza o banco.

Nunca capture `$this->db`, uma conexão SMTP ou o superobjeto do CI em uma closure assíncrona. Recursos e memória não são compartilhados entre processos.

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

`Async::pool()` lança se uma tarefa falhar como exceção e cancela as restantes; por isso o transporte deve preferencialmente retornar `array('sent' => false, 'error' => '...')` para falhas esperadas. O limite de quatro evita criar um processo sem controle para cada e-mail.

No Windows, rode um comando CLI do CI3 pelo Agendador de Tarefas, por exemplo `C:\\php\\php.exe C:\\app\\index.php email_worker run`, com repetição e usuário de serviço. Em Linux, o mesmo controller CLI pode ser acionado por cron ou supervisor. Não use a persistência em memória nesse cenário: cada execução começaria vazia.

### 9. Checklist de produção

- mantenha a chave única de idempotência e trate sua violação;
- recupere claims abandonados e defina máximo de tentativas/dead-letter;
- registre `queue_id`, tentativa, duração e erro sem corpo sensível;
- use timeout tanto no `Async` quanto no cliente SMTP/API;
- preserve o erro original em tamanho limitado;
- teste os dois repositórios pelo mesmo contract test;
- use SQL Server para múltiplos processos; memória é somente local;
- mantenha efeitos externos fora de transações longas de banco;
- faça shutdown gracioso e deixe o item recuperável após interrupção.

## Parte 2 — Plataforma de assinatura de contratos em Laravel (PHP 8+)

Agora criaremos um exemplo de assinatura recorrente de contratos. O Laravel cuida de HTTP, Eloquent, transações e filas; o Maybe explicita validação, ausência e erros do domínio. Não substitua a fila durável do Laravel por subprocessos dentro de uma request web.

### 1. Fluxo e modelo de dados

O fluxo é:

1. criar um contrato como `draft`;
2. localizar o plano de assinatura ativo;
3. criar a cobrança no provedor com chave idempotente;
4. ativar a assinatura e o contrato na mesma transação local;
5. publicar um job para solicitar a assinatura eletrônica;
6. enfileirar o e-mail de convite pelo serviço da primeira parte ou por um adapter Laravel.

Tabelas mínimas: `plans`, `contracts`, `subscriptions` e `signature_requests`. Use índices únicos para `contracts.uuid`, `subscriptions.provider_reference` e a chave idempotente do provedor.

### 2. DTO de criação

O código da aplicação pode usar recursos do PHP 8+, embora a API do Maybe permaneça compatível com 7.4.

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

### 3. Repositório com `Option`

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

O caso ausente vira um erro de domínio apenas quando o caso de uso exige um plano:

```php
$planResult = $plans->activeByCode($data->planCode)
    ->okOr(['code' => 'plan_not_found', 'retryable' => false]);
```

### 4. Gateway externo com `Result`

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

O gateway captura exceções porque está na borda de uma API que lança. O restante do domínio recebe um erro estruturado e decide se deve fazer retry.

### 5. Caso de uso transacional

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

Em produção, uma cobrança externa bem-sucedida seguida de falha local exige compensação ou, preferencialmente, saga/outbox. Uma transação SQL não torna a API do provedor atômica. A chave idempotente permite repetir o comando sem nova cobrança.

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

### 7. Job de assinatura e convite

O job pode chamar o provedor de assinatura e transformar a resposta em `Result`. Após persistir a solicitação, ele enfileira o convite com uma chave determinística:

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

Para um Laravel distribuído, implemente `EmailQueueRepository` com Eloquent/SQL Server ou adapte `EmailQueueService` para publicar um job Laravel. O contrato do serviço continua igual.

### 8. Onde usar `Async` no Laravel

Prefira Laravel Queue para cobrança, assinatura e e-mail: ela é durável, observável e possui retry. `Maybe\\Async` é apropriado dentro de um comando CLI para tarefas independentes, puras ou facilmente repetíveis, como gerar previews de páginas já persistidas:

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

Não passe models Eloquent conectados, containers, conexões PDO ou closures que capturem serviços. Passe arrays serializáveis, recrie recursos no filho e persista no processo pai. No Windows valem os mesmos requisitos de `proc_open` e PHP CLI da primeira parte.

## Estratégia de testes

1. teste `QueueEmailDTO` com destinatário inválido e limites de texto;
2. execute o mesmo contract test contra os repositórios em memória e SQL Server;
3. prove que duas chamadas com a mesma chave retornam uma única mensagem;
4. execute dois workers concorrentes e confirme que um item é reivindicado uma vez;
5. simule timeout e falha transitória para validar backoff e dead-letter;
6. no Laravel, use fakes dos gateways e `Queue::fake()`;
7. teste separadamente a compensação/outbox após sucesso remoto e falha local;
8. mantenha um teste de integração real para cada driver/serviço externo homologado.

O ganho central não é encadear métodos: é tornar cada fronteira explícita. `Schema` e `DTO` protegem a entrada, `Option` diferencia ausência, `Result` diferencia sucesso de falha recuperável e `Async` fica restrito ao trabalho realmente independente. A porta de persistência permite trocar memória, SQL Server e Eloquent sem reescrever a regra de negócio.
