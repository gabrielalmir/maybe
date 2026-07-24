# Referência de API

Assinaturas completas de cada tipo público, atualizadas para a **0.3.0**. Para prosa e exemplos, siga os guias por módulo; esta página é a consulta rápida.

## Option&lt;T&gt;

`Maybe\Option\Option` — um valor opcional, `Some` ou `None`.

| Membro | Assinatura | Descrição |
| --- | --- | --- |
| `Option::some` | `some($value): Option<T>` | Envolve um valor presente (nunca `null`). |
| `Option::none` | `none(): Option<mixed>` | O option vazio. |
| `Option::fromNullable` | `fromNullable($value): Option<T>` | `null` → `None`, senão `Some`. |
| `map` | `map(callable $fn): Option` | Transforma o valor; resultado `null` colapsa para `None`. |
| `flatMap` | `flatMap(callable $fn): Option` | Encadeia operação que retorna `Option`. |
| `filter` | `filter(callable $predicate): Option` | Mantém o valor só se o predicado for verdadeiro. |
| `match` | `match(callable $onSome, callable $onNone)` | Trata os dois ramos, retorna o resultado. |
| `unwrap` | `unwrap()` | O valor, ou lança `UnwrapNoneException` em `None`. |
| `unwrapOr` | `unwrapOr($default)` | O valor, ou fallback imediato. |
| `unwrapOrElse` | `unwrapOrElse(callable $fn)` | O valor, ou fallback lazy. |
| `expect` | `expect(string $message)` | O valor, ou lança com sua mensagem. |
| `okOr` | `okOr($error): Result` | `Some`→`Ok`, `None`→`Err($error)`. |
| `okOrElse` | `okOrElse(callable $fn): Result` | Como `okOr`, erro calculado lazy. |
| `isSome` / `isNone` | `(): bool` | Inspeciona a variante. |

## Result&lt;T, E&gt;

`Maybe\Result\Result` — um sucesso (`Ok`) ou uma falha tipada (`Err`).

| Membro | Assinatura | Descrição |
| --- | --- | --- |
| `Result::ok` | `ok($value): Result<T,mixed>` | Um sucesso. |
| `Result::err` | `err($error): Result<mixed,E>` | Uma falha tipada. |
| `map` | `map(callable $fn): Result` | Transforma o valor de sucesso. |
| `mapErr` | `mapErr(callable $fn): Result` | Transforma o valor de erro. |
| `andThen` | `andThen(callable $fn): Result` | Encadeia op falível; curto-circuita em `Err`. |
| `orElse` | `orElse(callable $fn): Result` | Recupera de `Err`; passa `Ok` adiante. |
| `match` | `match(callable $onOk, callable $onErr)` | Trata os dois ramos. |
| `unwrap` | `unwrap()` | O valor, ou lança `UnwrapErrException` em `Err`. |
| `unwrapErr` | `unwrapErr()` | O erro, ou lança `UnwrapOkException` em `Ok`. |
| `unwrapOr` | `unwrapOr($default)` | O valor, ou fallback imediato. |
| `unwrapOrElse` | `unwrapOrElse(callable $fn)` | O valor, ou fallback calculado do erro. |
| `expect` | `expect(string $message)` | O valor, ou lança com sua mensagem. |
| `okOption` | `okOption(): Option` | `Ok(v)`→`Some(v)`, `Err`→`None`. |
| `errOption` | `errOption(): Option` | `Err(e)`→`Some(e)`, `Ok`→`None`. |
| `isOk` / `isErr` | `(): bool` | Inspeciona a variante. |

## Schema

`Maybe\Schema\Schema` — builders de validadores imutáveis. Todo modificador retorna uma nova instância.

| Builder | Assinatura |
| --- | --- |
| `Schema::string` | `string(): StringSchema` |
| `Schema::int` | `int(): IntSchema` |
| `Schema::bool` | `bool(): BoolSchema` |
| `Schema::date` | `date(): DateSchema` |
| `Schema::enumeration` | `enumeration(array $allowedValues): EnumSchema` |
| `Schema::arrayOf` | `arrayOf(SchemaInterface $itemSchema): ArraySchema` |
| `Schema::shape` | `shape(array $shape): ObjectSchema` |
| `Schema::option` | `option(SchemaInterface $inner): OptionSchema` |

**Modificadores:** `StringSchema`: `->trimmed()`, `->min(int)`, `->max(int)`, `->regex(string)`. `IntSchema`: `->min(int)`, `->max(int)`. `DateSchema`: `->format(string)`, `->min(DateTimeImmutable)`, `->max(DateTimeImmutable)`. `ObjectSchema`: `->allowUnknown()`.

**Pontos de entrada (todos os schemas):** `->safeParse($input): Result<T, ValidationErrorBag>` (nunca lança) e `->parse($input): T` (lança `ValidationException`). `->transform(callable): SchemaInterface`.

## Erros de validação (0.3.0)

Value objects Tell-Don't-Ask — **não** existem getters `path()`, `message()`, `code()`, `all()` ou `first()`.

`Maybe\Schema\ValidationErrorBag` — coleção de primeira classe, `Countable` e iterável.

| Membro | Assinatura | Descrição |
| --- | --- | --- |
| `count` | `count(): int` | Número de erros (`Countable`). |
| `isEmpty` | `isEmpty(): bool` | Se não há erros. |
| iteração | `foreach ($bag as $error)` | Entrega itens `ValidationError`. |
| `describe` | `describe(): string[]` | Uma linha `"path: message"` por erro. |
| `summary` | `summary(): string` | Primeira linha mais "(and N more…)". |
| `toArray` | `toArray(): array[]` | Fronteira de serialização: linhas `['path','message','code']`. |
| `withError` / `merge` | `(...): self` | Adição / combinação imutável. |

`Maybe\Schema\ValidationError`: `describedAs(): string`, `underField(string): self`, `underIndex(int): self`, `toArray(): array{path,message,code}`. Crie um com um `Path`: `new ValidationError(Path::field('email'), 'message', 'code')`.

`Maybe\Schema\Path`: `Path::root(): self`, `Path::field(string): self`, `->underField(string): self`, `->underIndex(int): self`, `->toString(): string`.

## DTO

`Maybe\DTO\DTO` — base abstrata que mapeia input validado em um objeto tipado.

| Membro | Assinatura | Descrição |
| --- | --- | --- |
| `schema` | `abstract static schema(): ObjectSchema` | Define a forma. |
| `fromValidated` | `abstract static protected fromValidated(array): static` | Constrói a instância a partir dos dados validados. |
| `fromArray` | `static fromArray(array $input): Result<static, ValidationErrorBag>` | Valida, nunca lança. |
| `parse` | `static parse(array $input): static` | Valida, lança em input inválido. |

## Async

`Maybe\Async\Async` e `AsyncFuture` — concorrência via processos filhos (`proc_open`).

| Membro | Assinatura | Descrição |
| --- | --- | --- |
| `async` | `async(callable $task, array $args = [], array $options = []): AsyncFuture` | Inicia uma task. `options: ['timeout' => 2.5]`. Envolve `Async::run`. |
| `await` | `await($futureOrArray)` | Resolve um future, ou um array via `Async::all`. |
| `Async::run` | `run(callable $task, array $args = [], array $options = []): AsyncFuture` | Igual a `async()`, chamado direto na classe. |
| `Async::all` | `all(array $futures): array` | Espera todos (chaves preservadas). |
| `Async::race` | `race(array $futures)` | O primeiro a terminar vence. |
| `Async::pool` | `pool(array $tasks, int $limit = 5, array $options = []): array` | Concorrência limitada. |
| `Async::setDefaultTempDir` | `setDefaultTempDir(string $tempDir): void` | Sobrescreve onde os arquivos temporários do worker são escritos. |
| `Async::setDefaultTimeout` | `setDefaultTimeout(?float $seconds): void` | Timeout padrão por task quando `options['timeout']` é omitido. |
| `Async::setDefaultPollInterval` | `setDefaultPollInterval(int $microseconds): void` | Intervalo de polling padrão usado ao esperar um future. |
| `AsyncFuture` | `->then(fn)`, `->catch(fn)`, `->finally(fn)` | Registra callbacks. |
| | `->resolve()` | Bloqueia até terminar (ou timeout). |
| | `->pending(): bool`, `->cancel()` | Checagem não bloqueante / mata o processo. |

## Funções helper globais

Auto-carregadas (namespace `Maybe\`): `some()`, `none()`, `fromNullable()`, `ok()`, `err()`, `stringSchema()`, `intSchema()`, `boolSchema()`, `dateSchema()`, `enumSchema()`, `arraySchema()`, `objectSchema()`, `optionSchema()`, `async()`, `await()`.

> Escrevendo código com um assistente de IA? Aponte-o para o [`llms.txt`](/llms.txt) para as mesmas assinaturas em formato amigável a LLM.
