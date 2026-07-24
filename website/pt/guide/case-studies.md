# Estudos de Caso

A página de [Receitas](/pt/guide/recipes) mostra snippets isolados. Esta página percorre três cenários corporativos reais de ponta a ponta — o risco de negócio, o código legado que erra, e como os tipos do Maybe tornam o modo de falha impossível de ignorar. Cada trecho abaixo é copiado de um arquivo executável em [`examples/`](https://github.com/gabrielalmir/maybe/tree/main/examples), verificado contra o código-fonte real da biblioteca.

## 1. E-mail transacional que não pode derrubar o checkout

**O risco de negócio.** Um e-mail de confirmação de pedido falha ao enviar. Se essa falha não for tratada deliberadamente, uma de duas coisas ruins acontece: todo o checkout quebra por causa de um efeito colateral não-crítico, ou a falha é silenciosamente engolida e ninguém nunca descobre que o cliente não foi notificado.

**Como isso costuma aparecer em código legado:**

```php
// Silenciosamente invisível:
@mail($to, $subject, $body);

// "Tratado", mas o resultado é descartado:
try {
    $mailer->send($to, $subject, $body);
} catch (\Exception $e) {
    error_log($e->getMessage());
}
```

Nas duas versões, quem chama não tem como saber se o cliente foi realmente notificado — e não consegue distinguir "o e-mail estava malformado" (retry não ajuda) de "o relay SMTP deu timeout" (retry pode ajudar).

**Com o Maybe:** valide a mensagem antes de gastar uma chamada de rede, envolva o envio SMTP em um `Result`, use `orElse()` para cair para um relay secundário, e confirme o pedido independentemente do resultado do e-mail:

```php
$sendConfirmationEmail = static function (array $message) use ($emailSchema, $primary, $secondary): Result {
    return $emailSchema->safeParse($message)
        ->mapErr(fn (ValidationErrorBag $errors): array => ['retryable' => false, 'reason' => $errors->summary()])
        ->andThen(function (array $valid) use ($primary, $secondary): Result {
            return tryCatch(fn () => $primary->send($valid['to'], $valid['subject'], $valid['body']))
                ->mapErr(fn (\Throwable $e): string => $e->getMessage())
                ->orElse(fn () => tryCatch(fn () => $secondary->send($valid['to'], $valid['subject'], $valid['body']))
                    ->mapErr(fn (\Throwable $e): string => $e->getMessage()))
                ->mapErr(fn (string $reason): array => ['retryable' => true, 'reason' => $reason]);
        });
};

// O pedido é confirmado de qualquer forma — o envio do e-mail é um efeito
// colateral, não um pré-requisito para o pedido existir.
$emailResult->match(
    fn (string $ref): string => "enviado ({$ref})",
    fn (array $error): string => $error['retryable']
        ? "na fila para retry ({$error['reason']})"
        : "rejeitado, precisa de correção de dado ({$error['reason']})"
);
```

**Por que isso importa:** o payload de erro mantém `retryable` explícito. Um e-mail malformado e um relay SMTP instável são *problemas diferentes* — um precisa de correção de dado, o outro precisa de fila de retry — e o tipo evita que os dois sejam tratados igual por acidente.

**Quando não usar este padrão:** se o envio do e-mail realmente precisa bloquear a transação (ex.: uma senha de uso único que o usuário precisa imediatamente), não desacople — esse é um caso em que a falha *deve* propagar.

Arquivo executável completo: [`examples/scenario-transactional-email.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/scenario-transactional-email.php)

## 2. Envio de pedidos ao SAP sem perder dado silenciosamente

**O risco de negócio.** Um pedido confirmado precisa ser lançado no SAP (via RFC/OData/BAPI). Chamadas ao SAP falham por motivos estruturados: documento duplicado, centro de custo ausente, sessão expirada, timeout de rede. Código de integração legado costuma colapsar tudo isso na mesma não-resposta:

```php
if (!$sap->post($payload)) {
    return false; // qual erro? ninguém sabe.
}
```

O risco real aqui não é o erro em si — é o pedido ser confirmado ao cliente, nunca ser criado no SAP, e ninguém perceber até a conciliação financeira, semanas depois.

**Com o Maybe:** valide o payload de saída contra o formato esperado pelo SAP *antes* do round-trip de rede, depois classifique a falha pelo tipo de exceção — um problema de conexão é retryable, uma violação de regra de negócio não é:

```php
final class SapConnectionException extends \RuntimeException {} // retryable
final class SapBusinessException extends \RuntimeException {}   // não-retryable

$pushOrderToSap = static function (array $order) use ($orderSchema, $sap): Result {
    return $orderSchema->safeParse($order)
        ->mapErr(fn (ValidationErrorBag $errors): array => ['retryable' => false, 'reason' => 'invalid_payload: ' . $errors->summary()])
        ->andThen(function (array $payload) use ($sap): Result {
            return tryCatch(fn () => $sap->postSalesOrder($payload))
                ->mapErr(fn (\Throwable $e): array => [
                    'retryable' => $e instanceof SapConnectionException,
                    'reason' => $e->getMessage(),
                ]);
        });
};
```

Quem chama roteia o resultado para dois grupos em vez de um único log de falha indiferenciado:

```php
$sapResult->match(
    fn (string $sapDocNumber): string => "criado no SAP ({$sapDocNumber})",
    function (array $error) use ($order, &$requeued, &$manualReview): string {
        if ($error['retryable']) {
            $requeued[] = $order['id'];
            return "reenfileirado para retry ({$error['reason']})";
        }

        $manualReview[] = $order['id'];
        return "enviado para revisão manual ({$error['reason']})";
    }
);
```

**Por que isso importa:** o pedido é confirmado localmente de qualquer forma — o SAP fora do ar não derruba o checkout junto — mas um erro de negócio (material desconhecido, centro de custo ausente) para de ser retentado para sempre em vez de falhar silenciosamente do mesmo jeito a cada nova tentativa.

**Quando não usar este padrão:** se o seu processo realmente não pode prosseguir sem a confirmação do SAP primeiro (ex.: alocação de estoque em tempo real), não desacople — torne a chamada ao SAP síncrona e parte da mesma fronteira de transação.

Arquivo executável completo: [`examples/scenario-sap-order-integration.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/scenario-sap-order-integration.php)

## 3. Validação de contrato com regras de negócio cross-field

**O risco de negócio.** Validação de contrato espalhada em um controller como uma cadeia de `if`s permite que um contrato seja meio-salvo em estado inválido, e produz mensagens de erro pouco estruturadas demais para uma tela de revisão jurídica/operações apontar o campo exato.

**Uma limitação real que vale conhecer:** `Schema` não tem validação cross-field nativa (ex.: "a data de fim deve ser depois da data de início") nem checagem de lista condicional obrigatória (ex.: "estas cláusulas devem estar todas presentes"). A correção idiomática **não é** uma API de schema maior — é encadear uma função de regra de negócio que retorna `Result` com `andThen()` logo após o `safeParse()`, reaproveitando o mesmo `ValidationErrorBag` para que os dois estágios reportem através de um único formato de erro uniforme:

```php
function checkBusinessRules(array $contract): Result
{
    $errors = new ValidationErrorBag();

    if ($contract['ends_at'] <= $contract['starts_at']) {
        $errors = $errors->withError(
            new ValidationError(Path::field('ends_at'), 'End date must be after the start date', 'contract.invalid_period')
        );
    }

    foreach (array_diff(MANDATORY_CLAUSES, $contract['clauses']) as $clause) {
        $errors = $errors->withError(
            new ValidationError(Path::field('clauses'), "Missing mandatory clause: {$clause}", 'contract.missing_clause')
        );
    }

    return $errors->isEmpty() ? Result::ok($contract) : Result::err($errors);
}

$validateContract = static function (array $input) use ($contractSchema): Result {
    return $contractSchema->safeParse($input)->andThen('checkBusinessRules');
};
```

Como os dois estágios retornam `Result<array, ValidationErrorBag>`, quem chama trata erros estruturais (formato de CNPJ inválido, valor do contrato abaixo de zero) e erros de regra de negócio (intervalo de datas inválido, cláusula obrigatória ausente) através do mesmíssimo `match()`:

```php
$result->match(
    fn (array $valid): string => "aprovado (valor: {$valid['value_in_cents']} centavos)",
    function (ValidationErrorBag $errors): string {
        return implode("\n", $errors->describe());
    }
);
```

**Por que isso importa:** uma UI de revisão jurídica/operações pode renderizar todo motivo de rejeição — venha do estágio de schema ou do estágio de regra de negócio — a partir do mesmíssimo formato `ValidationErrorBag::toArray()`, com um `path()` no estilo JSONPath apontando para o campo problemático.

**Quando não usar este padrão:** não recorra a um estágio de regra de negócio com `andThen()` para algo que o Schema já expressa nativamente (ex.: um `int` limitado ou um `regex()` — use o modificador do schema, não reinvente em uma closure).

Arquivo executável completo: [`examples/scenario-contract-validation.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/scenario-contract-validation.php)
