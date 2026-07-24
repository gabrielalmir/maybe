# Primeiros Passos

`Maybe` é uma biblioteca PHP para lógica de negócio explícita e previsível. Ela combina cinco blocos:

- **`Option<T>`** — fluxo seguro para valores opcionais
- **`Result<T, E>`** — sucesso/erro tipados sem exceções como controle de fluxo
- **`Schema`** — parsing e validação imutáveis
- **`DTO`** — mapeamento validado de objetos de entrada
- **`Async`** — execução concorrente via processos (`proc_open`), com foco em PHP 7.4 + Windows + CodeIgniter 3

## Requisitos

- PHP `>= 7.4`
- Composer

## Instalação

```bash
composer require gabrielalmir/maybe
```

## Primeiros passos

Se você trabalha em ambiente corporativo ou legado, comece por `Schema`, `DTO` e `Result` antes de adotar `Async`:

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

$result->match(
    fn (array $data) => saveUser($data),
    fn ($errors) => respondWithErrors($errors->toArray())
);
```

## Helpers funcionais

As seguintes funções com namespace são carregadas automaticamente:

- Option/Result: `some()`, `none()`, `fromNullable()`, `ok()`, `err()`
- Schema: `stringSchema()`, `intSchema()`, `boolSchema()`, `dateSchema()`, `enumSchema()`, `arraySchema()`, `objectSchema()`, `optionSchema()`
- Async: `async()`, `await()`

## Próximos passos

- [Option](/pt/guide/option) — valores opcionais sem checagens de null
- [Result](/pt/guide/result) — tratamento de erros como dados
- [Schema](/pt/guide/schema) — validação e parsing
- [DTO](/pt/guide/dto) — objetos de entrada validados
- [Async](/pt/guide/async) — concorrência baseada em processos
- [CodeIgniter 3](/pt/guide/codeigniter-3) — integração com apps CI3 legados
- [Migração Incremental](/pt/guide/migration) — adotando Maybe em uma base existente
