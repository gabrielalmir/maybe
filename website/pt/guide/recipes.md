# Receitas

Scripts executáveis e independentes para problemas comuns. Cada arquivo vive em [`examples/`](https://github.com/gabrielalmir/maybe/tree/main/examples) no repositório e pode ser executado diretamente com `php examples/<arquivo>.php` após instalar as dependências (`composer install`).

> Procurando o contexto de negócio por trás desses snippets — o risco, o código legado, por que isso importa? Veja [Estudos de Caso](/pt/guide/case-studies) para percursos completos: e-mail transacional, integração com SAP e validação de contratos.

## Checkout com cupons e autorização de pagamento

`Option` para um código de cupom opcional, `Result` encadeando uma etapa de desconto com uma etapa de autorização de pagamento.

[`examples/option-result.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/option-result.php)

## Cadastro de cliente com DTO completo

Um exemplo completo de `DTO`: schema com `trimmed()`, `regex()`, `int` limitado, um campo opcional via `Schema::option()`, e relatório de erros estruturado via `ValidationErrorBag`.

[`examples/schema-dto.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/schema-dto.php)

## Lookup de repositório com Option

Um repositório retorna `Option` em vez de `null`; uma camada de service converte a ausência em um erro `Result` tipado só onde realmente precisa, usando `okOr()` e `andThen()`.

```php
final class CustomerRepository
{
    public function findById(int $id): Option
    {
        // ...
        return Option::none();
    }
}

$service = static function (CustomerRepository $repo, int $id): Result {
    return $repo->findById($id)
        ->okOr('customer_not_found')
        ->andThen(fn (array $customer): Result =>
            $customer['active'] ? Result::ok($customer['name']) : Result::err('customer_inactive')
        );
};
```

[`examples/recipe-repository-lookup.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/recipe-repository-lookup.php)

## Envolvendo uma chamada legada baseada em exceções

Em uma fronteira com um SDK de terceiros ou código legado que só comunica falha via exceções, envolva a chamada uma vez em vez de deixar exceções vazarem para código tipado com `Result`:

```php
function tryCatch(callable $fn): Result
{
    try {
        return Result::ok($fn());
    } catch (\Throwable $e) {
        return Result::err($e);
    }
}
```

[`examples/recipe-safe-external-call.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/recipe-safe-external-call.php)

## Importação em lote com relatório de erro por linha

Valide um lote inteiro (ex.: importação de CSV) em uma única passada com `Schema::arrayOf()` — os erros reportam o índice da linha no caminho (`$[1].email`). Para importações parciais, valide linha por linha e mantenha dois grupos: importados e rejeitados.

[`examples/recipe-batch-import.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/recipe-batch-import.php)

## Mais receitas de Async

- [`examples/async-basic.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/async-basic.php) — uma única task em background
- [`examples/async-all-race.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/async-all-race.php) — `Async::all()` e `Async::race()`
- [`examples/async-pool.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/async-pool.php) — concorrência limitada com `Async::pool()`
- [`examples/async-chain-timeout-cancel.php`](https://github.com/gabrielalmir/maybe/blob/main/examples/async-chain-timeout-cancel.php) — `then()`/`catch()`/`finally()`, timeouts e cancelamento

Para mais padrões antes/depois (validação de filtros, padronização de respostas de erro JSON, conversão de retornos legados `null`/`false`), veja o [guia de Receitas Práticas](https://github.com/gabrielalmir/maybe/blob/main/docs/04-practical-recipes.md) no repositório.

## Usando um assistente de IA com o Maybe

Se você usa um assistente de IA (Claude, Copilot, Cursor etc.) para escrever código com esta biblioteca, aponte-o para o [`llms.txt`](/llms.txt) — uma referência de API condensada e exata, pensada para consumo por LLMs, para que ele não precise adivinhar nomes de métodos ou assinaturas.
