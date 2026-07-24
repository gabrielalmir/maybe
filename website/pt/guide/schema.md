# Schema

`Schema` oferece parsing e validação imutáveis e componíveis, inspirados no Zod. Construa um schema uma vez, reutilize em qualquer lugar e receba erros estruturados em vez de exceções.

## Uso básico

```php
use Maybe\Schema\Schema;

$schema = Schema::shape([
    'email' => Schema::string()->trimmed()->min(5),
    'age' => Schema::int()->min(18),
]);

$result = $schema->safeParse([
    'email' => '  user@example.com  ',
    'age' => 23,
]);
```

`safeParse` retorna um `Result`: `Ok` com os dados validados (e transformados — ex.: com trim aplicado), ou `Err` com um `ValidationErrorBag`.

```php
$result->match(
    fn (array $data) => saveUser($data),
    fn ($errors) => respondWith422($errors->toArray())
);
```

## Builders disponíveis

- `Schema::string()` — com modificadores como `trimmed()`, `min()`, `max()`
- `Schema::int()` — com limites `min()` / `max()`
- `Schema::bool()`
- `Schema::date()`
- `Schema::enumeration(['active', 'inactive'])`
- `Schema::arrayOf($itemSchema)` — valida cada item, reportando caminhos aninhados
- `Schema::shape([...])` — validação de objetos/arrays associativos
- `Schema::option($inner)` — aceita `null`, envolvendo o valor em um `Option`

## Relatório de erros

`ValidationErrorBag` é uma coleção de primeira classe: é contável, iterável e se renderiza (Tell, Don't Ask) em vez de expor a lista crua por getters.

```php
$errors->count();               // int (Countable)
$errors->isEmpty();             // bool

foreach ($errors as $error) {   // iterável
    echo $error->describedAs(); // "$.age: Value must be >= 18"
}

$errors->describe();   // ['$.age: Value must be >= 18', ...] — uma linha por erro
$errors->summary();    // "$.age: Value must be >= 18 (and 2 more errors)"
$errors->toArray();    // [['path' => '$.age', 'message' => '...', 'code' => '...'], ...]
```

Use `describe()` para linhas legíveis e `toArray()` na fronteira de serialização (ex.: uma resposta JSON de API). Estruturas aninhadas reportam caminhos completos (ex.: `$[2].email`), o que torna respostas de erro de API precisas.

## Reuso e imutabilidade

Cada modificador retorna uma nova instância de schema, então schemas base compartilhados são seguros:

```php
$email = Schema::string()->trimmed()->min(5);

$signup = Schema::shape(['email' => $email, 'password' => Schema::string()->min(8)]);
$invite = Schema::shape(['email' => $email]);
```
