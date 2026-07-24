# Option

`Option<T>` representa um valor que pode ou não existir — `Some(valor)` ou `None` — substituindo checagens de `null` por uma API explícita e encadeável.

## Criando um Option

```php
use Maybe\Option\Option;

$some = Option::some('ana');
$none = Option::none();

// A porta de entrada mais comum: converter um nullable em Option
$name = Option::fromNullable($payload['name'] ?? null);
```

## Transformando valores

```php
$name = Option::fromNullable($payload['name'] ?? null)
    ->map('trim')
    ->flatMap(fn (string $value): Option =>
        $value === '' ? Option::none() : Option::some($value)
    )
    ->unwrapOr('guest');
```

- `map(fn)` transforma o valor interno. Se o callback retornar `null`, o resultado colapsa para `None` (mesma semântica de `fromNullable`).
- `flatMap(fn)` encadeia uma operação que também retorna um `Option`.
- `filter(predicate)` mantém o valor apenas se o predicado for verdadeiro.

```php
$adult = Option::some($age)->filter(fn (int $a): bool => $a >= 18);
```

## Extraindo valores

```php
$option->unwrap();                        // valor, ou lança UnwrapNoneException em None
$option->unwrapOr('default');             // valor ou fallback imediato
$option->unwrapOrElse(fn () => custoso());// valor ou fallback lazy
$option->expect('nome é obrigatório');    // valor, ou lança com sua mensagem
```

Prefira `match` quando os dois ramos precisam de tratamento:

```php
$greeting = $option->match(
    fn (string $name): string => "Olá, {$name}",
    fn (): string => 'Olá, visitante'
);
```

## Convertendo para Result

Quando a ausência deve virar um erro tipado, converta para um [`Result`](/pt/guide/result):

```php
$result = Option::fromNullable($user)->okOr('user_not_found');
$result = Option::fromNullable($user)->okOrElse(fn () => makeError());
```

## Resumo da API

| Método | Descrição |
| --- | --- |
| `Option::some($v)` / `Option::none()` / `Option::fromNullable($v)` | Construtores |
| `map(fn)` / `flatMap(fn)` / `filter(fn)` | Transformações |
| `match(onSome, onNone)` | Tratamento exaustivo |
| `unwrap()` / `unwrapOr($d)` / `unwrapOrElse(fn)` / `expect($msg)` | Extração |
| `okOr($err)` / `okOrElse(fn)` | Conversão para `Result` |
| `isSome()` / `isNone()` | Inspeção |
