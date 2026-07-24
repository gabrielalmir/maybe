# Result

`Result<T, E>` representa uma operação que ou teve sucesso com um valor (`Ok`) ou falhou com um erro tipado (`Err`) — sem exceções como controle de fluxo.

## Criando um Result

```php
use Maybe\Result\Result;

function loadUser(int $id): Result
{
    if ($id <= 0) {
        return Result::err('invalid_id');
    }

    return Result::ok(['id' => $id, 'name' => 'Ana']);
}
```

## Encadeando operações falíveis

`andThen` é o carro-chefe: encadeia outra operação que também pode falhar, com curto-circuito no primeiro `Err`.

```php
$invoice = loadUser(10)
    ->andThen(fn (array $user): Result => checkSubscription($user))
    ->andThen(fn (array $user): Result => createInvoice($user))
    ->map(fn (array $invoice): array => addTotals($invoice));
```

`orElse` é o espelho — recupera de um erro produzindo um novo `Result`:

```php
$user = loadFromCache($id)
    ->orElse(fn (string $error): Result => loadFromDatabase($id));
```

`mapErr` transforma o payload de erro sem tocar em valores de sucesso:

```php
$result = loadUser($id)->mapErr(fn (string $e): array => ['code' => $e]);
```

## Extraindo valores

```php
$result->unwrap();                             // valor, ou lança UnwrapErrException em Err
$result->unwrapErr();                          // erro, ou lança UnwrapOkException em Ok
$result->unwrapOr($default);                   // valor ou fallback imediato
$result->unwrapOrElse(fn ($e) => recover($e)); // valor ou fallback calculado do erro
$result->expect('usuário deve existir');       // valor, ou lança com sua mensagem
```

Prefira `match` quando os dois ramos precisam de tratamento:

```php
$message = loadUser(10)->match(
    fn (array $user): string => 'Usuário: ' . $user['name'],
    fn (string $error): string => 'Erro: ' . $error
);
```

## Convertendo para Option

```php
$result->okOption();   // Ok(v) -> Some(v), Err(_) -> None
$result->errOption();  // Err(e) -> Some(e), Ok(_) -> None
```

## Resumo da API

| Método | Descrição |
| --- | --- |
| `Result::ok($v)` / `Result::err($e)` | Construtores |
| `map(fn)` / `mapErr(fn)` | Transformar sucesso / erro |
| `andThen(fn)` / `orElse(fn)` | Encadear operações falíveis / recuperar |
| `match(onOk, onErr)` | Tratamento exaustivo |
| `unwrap()` / `unwrapErr()` / `unwrapOr($d)` / `unwrapOrElse(fn)` / `expect($msg)` | Extração |
| `okOption()` / `errOption()` | Conversão para `Option` |
| `isOk()` / `isErr()` | Inspeção |
