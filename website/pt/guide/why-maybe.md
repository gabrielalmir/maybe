# Por que Maybe?

O Maybe existe para tornar as duas maiores fontes de surpresa em runtime no PHP — `null` e exceções usadas como controle de fluxo — **visíveis no tipo de um valor**, para que o compilador-na-sua-cabeça (e quem revisa) as enxergue.

## O custo do `null`

`null` é o valor que cabe em tudo e não significa nada. Uma função que retorna `?User` obriga todo chamador a lembrar de uma checagem que o tipo não impõe:

```php
$user = $repo->find($id);
echo strtoupper($user->name()); // ok até $user ser null em produção
```

`Option<User>` torna a ausência um caso que você precisa tratar, não um que pode esquecer:

```php
$repo->find($id)
    ->map(fn (User $u) => strtoupper($u->name()))
    ->unwrapOr('desconhecido');
```

## O custo de exceções como controle de fluxo

Exceções são ótimas para falhas *inesperadas*. Usadas para as *esperadas* — um pagamento recusado, um cupom inválido, uma regra de negócio — elas transformam desfechos comuns em saltos invisíveis que não aparecem na assinatura:

```php
function charge(Order $o): string       // isso lança? você tem que ler o corpo
```

`Result<string, PaymentError>` coloca a falha no tipo de retorno, e `match()` obriga a tratar os dois caminhos na fronteira:

```php
charge($order)->match(
    fn (string $ref) => "pago: {$ref}",
    fn (PaymentError $e) => "recusado: {$e->reason()}"
);
```

Exceções continuam sendo para falhas genuinamente inesperadas (config quebrada, queda de I/O). O Maybe é para as *esperadas*.

## O que o Maybe te dá

Cinco blocos que compartilham uma filosofia — tornar sucesso, falha e ausência explícitos e tipados:

- **`Option`** — valores opcionais sem checagens de null espalhadas.
- **`Result`** — sucesso/erro como dado, composto com `andThen`/`orElse`.
- **`Schema`** — faz parse de input não confiável em dado validado na fronteira.
- **`DTO`** — dá ao dado validado um nome e um tipo.
- **`Async`** — concorrência por processos sem extensões.

## Como se compara

O Maybe não é a única opção, e não tenta bater especialistas no próprio jogo. Seu nicho é **um kit integrado que roda onde o PHP legado vive** — PHP 7.4, Windows, CodeIgniter 3.

| Você quer… | Ferramentas especialistas | Onde o Maybe encaixa |
| --- | --- | --- |
| Só `Option` | `phpoption/phpoption` | O Maybe junta com Result/Schema/DTO sob um estilo e pacote só. |
| Só `Result` | `GrahamCampbell/Result-Type` | Idem — mais `andThen`/`orElse`/`okOption` e uma filosofia de erro compartilhada. |
| Validação rica | `respect/validation`, `symfony/validator`, Zod (JS) | `Schema` é menor e retorna `Result`; combine com `andThen` para regras cross-field. |
| Async pesado | `spatie/async`, `amphp`, `reactphp` | `Async` mira PHP 7.4 + Windows sem `pcntl`/extensões; mais simples, não um event loop completo. |

**A vantagem do Maybe:** os cinco blocos são feitos para compor entre si (um `Schema` retorna um `Result`, um `DTO` envolve um `Schema`, um `Option` converte para `Result`), numa única dependência que instala em PHP 7.4.

## Quando *não* usar o Maybe

Honestidade primeiro — vá para outra coisa quando:

- Você está em PHP moderno (8.1+) e já padronizou em uma lib especialista madura com a qual está feliz.
- Você precisa de um event loop async completo com I/O não bloqueante — use `amphp`/`reactphp`, não o `Async`.
- Você precisa de um motor de validação com um grande catálogo de regras e mensagens i18n de fábrica — `symfony/validator` ou `respect/validation` podem encaixar melhor.
- Seu time prefere fortemente exceções e não vai adotar `Result` nas fronteiras — o valor vem do uso consistente.

Se você mantém PHP legado, quer caminhos de sucesso/erro tipados e valoriza uma única dependência pequena e agnóstica de framework, é exatamente para isso que o Maybe serve.

Próximo: o [Tutorial](/pt/guide/tutorial) constrói um fluxo validado real de ponta a ponta, ou vá para a [Referência de API](/pt/guide/api-reference).
