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

**Com o Maybe:** nomeie cada fronteira e mantenha o chamador focado no resultado:

```php
$emailResult = $emailSchema->safeParse($message)
    ->andThen(static fn (array $valid): Result => sendWithFallback($valid));

$emailResult->match(
    static fn (string $ref): string => "enviado ({$ref})",
    static fn (array $error): string => $error['retryable']
        ? "na fila para retry ({$error['reason']})"
        : "rejeitado: corrija o input ({$error['reason']})"
);
```

Os detalhes de transporte ficam em `sendWithFallback()`, que pode ser testado separadamente. A página que confirma o pedido só precisa decidir o que `Ok` ou `Err` significam.

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

**Com o Maybe:** mantenha validação, transporte e roteamento como três fronteiras nomeadas:

```php
$sapResult = $orderSchema->safeParse($order)
    ->andThen(static fn (array $payload): Result => postToSap($payload));

$sapResult->match(
    static fn (string $document): string => "criado no SAP ({$document})",
    static fn (array $error): string => routeSapFailure($order, $error)
);
```

`postToSap()` classifica uma falha de conexão como retryable, enquanto `routeSapFailure()` decide entre retry e revisão manual. Nenhuma decisão fica escondida em um controller.

**Por que isso importa:** o pedido é confirmado localmente de qualquer forma — o SAP fora do ar não derruba o checkout junto — mas um erro de negócio (material desconhecido, centro de custo ausente) para de ser retentado para sempre em vez de falhar silenciosamente do mesmo jeito a cada nova tentativa.

**Quando não usar este padrão:** se o seu processo realmente não pode prosseguir sem a confirmação do SAP primeiro (ex.: alocação de estoque em tempo real), não desacople — torne a chamada ao SAP síncrona e parte da mesma fronteira de transação.

Arquivo executável completo: [`examples/scenario-sap-order-integration.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/scenario-sap-order-integration.php)

## 3. Validação de contrato com regras de negócio cross-field

**O risco de negócio.** Validação de contrato espalhada em um controller como uma cadeia de `if`s permite que um contrato seja meio-salvo em estado inválido, e produz mensagens de erro pouco estruturadas demais para uma tela de revisão jurídica/operações apontar o campo exato.

**Uma limitação real que vale conhecer:** `Schema` não tem validação cross-field nativa nem checagem de lista condicional obrigatória. A correção idiomática é adicionar uma função de regra de negócio depois do `safeParse()`:

```php
$result = $contractSchema->safeParse($input)
    ->andThen('checkBusinessRules');

$result->match(
    static fn (array $valid): string => "aprovado (valor: {$valid['value_in_cents']} centavos)",
    static fn (ValidationErrorBag $errors): string => implode("\n", $errors->describe())
);
```

Os dois estágios retornam o mesmo `Result`, então erros estruturais e regras de negócio compartilham a mesma borda:

```php
$result->match(
    static fn (array $valid): string => renderApproved($valid),
    static fn (ValidationErrorBag $errors): string => renderErrors($errors)
);
```

**Por que isso importa:** uma UI de revisão jurídica/operações pode renderizar todo motivo de rejeição — venha do estágio de schema ou do estágio de regra de negócio — a partir do mesmíssimo formato `ValidationErrorBag::toArray()`, com um `path()` no estilo JSONPath apontando para o campo problemático.

**Quando não usar este padrão:** não recorra a um estágio de regra de negócio com `andThen()` para algo que o Schema já expressa nativamente (ex.: um `int` limitado ou um `regex()` — use o modificador do schema, não reinvente em uma closure).

Arquivo executável completo: [`examples/scenario-contract-validation.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/scenario-contract-validation.php)
