# Migração Incremental

Você não precisa de uma reescrita para adotar o Maybe. Cada bloco funciona sozinho, e eles se compõem conforme você avança.

## Ordem recomendada

1. **Schema nas fronteiras** — substitua validações ad-hoc com `isset`/`empty` por um schema em cada fronteira de entrada (request HTTP, importação de CSV, payload de fila). Nada mais muda ainda.
2. **DTOs para os inputs problemáticos** — onde dados validados cruzam camadas, envolva-os em um DTO para garantir o formato adiante.
3. **Result nos services** — métodos de service novos/alterados retornam `Result` em vez de lançar exceção ou retornar `false`/`null`. Quem chama faz `match` na borda.
4. **Option para lookups nullable** — repositórios retornam `Option` em vez de `null`, convertendo com `okOr()` quando um erro tipado é necessário.
5. **Async por último** — só depois que o time estiver confortável, e apenas para cargas isoladas e serializáveis.

## Convivendo com exceções

O Maybe não força um estilo tudo-ou-nada. Nas fronteiras com código baseado em exceções:

```php
use Maybe\Result\Result;

function tryCatch(callable $fn): Result
{
    try {
        return Result::ok($fn());
    } catch (\Throwable $e) {
        return Result::err($e);
    }
}
```

E na direção oposta, `unwrap()`/`expect()` convertem um `Err` de volta em exceção na camada onde exceções são a convenção.

## O que evitar

- Não envolva *tudo* em Option/Result no primeiro dia — comece onde os erros realmente doem.
- Não passe `Result` fundo em camadas que nunca o inspecionam; resolva-o na fronteira sensata mais próxima.
- Não use `unwrap()` como atalho em caminhos de produção — prefira `match`, `unwrapOr()` ou `expect()` com uma mensagem significativa.

Para orientação mais profunda, veja os docs do repositório: [padrões de uso](https://github.com/gabrielalmir/maybe/blob/main/docs/03-usage-patterns.md), [receitas práticas](https://github.com/gabrielalmir/maybe/blob/main/docs/04-practical-recipes.md) e [anti-padrões](https://github.com/gabrielalmir/maybe/blob/main/docs/05-anti-patterns.md).
