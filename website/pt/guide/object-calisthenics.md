# Object Calisthenics com Maybe

[Object Calisthenics](https://williamdurand.fr/2013/06/03/object-calisthenics/) é um conjunto de nove regras de Jeff Bay para escrever código que continua legível conforme cresce. Várias delas são difíceis de seguir em PHP puro — até você ter os tipos certos. As primitivas do Maybe tornam a maioria delas o jeito *natural* de escrever, não uma disciplina extra que você precisa lembrar.

Este guia vai regra por regra: o que ela pede e como `Option`, `Result`, `Schema` e `DTO` ajudam a chegar lá. A própria biblioteca segue essas regras (veja `src/`), então o código que você copia da documentação já lê assim.

## 1. Um nível de indentação por método

`if`/`foreach` aninhados são onde os métodos ficam ilegíveis. `match()` colapsa uma decisão de dois ramos em uma única expressão, e `andThen()` substitui a escada de checagens de sucesso aninhadas.

```php
// Antes — dois níveis, e crescendo
function price(?Order $order): int
{
    if ($order !== null) {
        if ($order->isPaid()) {
            return $order->total();
        }
    }
    return 0;
}

// Depois — um nível
function price(Order $order): int
{
    return $order->paidTotal()->unwrapOr(0);
}
```

## 2. Não use `else`

`else` quase sempre significa "duas coisas acontecendo aqui". `Option` e `Result` te dão exatamente dois ramos com nomes, tratados em um só lugar:

```php
$label = $user->match(
    fn (User $u): string => $u->name(),
    fn (): string => 'guest'
);
```

Na validação, `andThen()` encadeia o caminho feliz e faz curto-circuito nas falhas — sem `else`, sem escada de early-returns.

## 3. Envolva todos os primitivos e strings

Um `string $email` ou `array $payload` cru não carrega regra nenhuma. `Schema` faz o parse do input não confiável em dados validados na fronteira, e um `DTO` dá a esses dados um nome e um tipo:

```php
final class SignupData extends DTO
{
    public static function schema(): ObjectSchema
    {
        return Schema::shape([
            'email' => Schema::string()->trimmed()->regex('/^[^@\s]+@[^@\s]+\.[^@\s]+$/'),
            'age' => Schema::int()->min(18),
        ]);
    }
    // ...
}

$result = SignupData::fromArray($request); // Result<SignupData, ValidationErrorBag>
```

Depois da fronteira você passa um `SignupData`, nunca um array cru.

## 4. Coleções de primeira classe

Uma classe que envolve um array deve envolver *só* esse array. O próprio `ValidationErrorBag` do Maybe é o exemplo: guarda a lista de erros e nada mais, e é iterável e contável em vez de expor o array cru.

```php
foreach ($errors as $error) {
    echo $error->describedAs();
}
```

## 5. Um ponto por linha — com uma exceção deliberada

A regra mira a Lei de Deméter: não alcance *através* de um objeto para chegar a outro (`$order->customer()->address()->zip()`). Isso continua valendo evitar.

Mas uma **cadeia fluente do Maybe não é isso** — cada `->map()->andThen()->match()` retorna um novo objeto do *mesmo* tipo, então você nunca alcança através de colaboradores não relacionados. O Maybe abraça essas cadeias de propósito; elas são o núcleo legível da biblioteca, e este guia as trata como a exceção sancionada à regra 5.

```php
$invoice = loadUser($id)
    ->andThen(fn (User $u): Result => charge($u))
    ->map(fn (Charge $c): string => $c->reference())
    ->unwrapOr('unpaid');
```

## 6. Não abrevie

Nada específico da biblioteca aqui, mas tipos expressivos removem a *pressão* de abreviar: você escreve `unwrapOr('guest')`, não um truque terso de null-coalescing que implora por um comentário `// ...`.

## 7. Mantenha as entidades pequenas

Tipos pequenos compõem. O Maybe te empurra para muitos objetos pequenos e de propósito único — um schema por campo, um DTO por fronteira, um value object por conceito — em vez de um god-service com cem ramos.

## 8. No máximo duas variáveis de instância

Essa é a regra que mais código falha, e a correção é sempre a mesma: envolva campos relacionados em um value object. Os internos do schema do Maybe mostram o padrão — `StringSchema` guarda um `TextLength` e um `TextFormat` (dois objetos) em vez de quatro escalares soltos; `DateSchema` guarda um formato e um `DateBounds`.

Quando a sua classe quiser um terceiro campo, esse é o sinal de que dois deles pertencem juntos.

## 9. Sem getters, sem setters

"Tell, Don't Ask": objetos devem *fazer* coisas, não entregar seus internos para outro inspecionar. `ValidationError` se renderiza (`describedAs()`) e se re-parenteia (`underField()`) — nunca expõe `->path()` ou `->message()`. A única exceção aceita é a **fronteira de serialização**: `toArray()` existe justamente para transformar um objeto em array cru na borda (uma resposta JSON), e em nenhum outro lugar.

```php
// Ask (evite): puxar campos para formatar em outro lugar
$line = $error->path() . ': ' . $error->message(); // esses getters não existem

// Tell (faça): o erro sabe se descrever
$line = $error->describedAs();

// Fronteira de serialização (ok): transformar o bag em corpo de resposta
return json_encode(['errors' => $errors->toArray()]);
```

`Option::unwrap()` e `Result::unwrap()` **não** são getters nesse sentido — são o núcleo intencional e checado desses tipos (e você geralmente deve preferir `match()`/`unwrapOr()` a eles, de qualquer forma).

## As duas exceções, ditas claramente

Aplicar as regras estritamente ainda deixa dois pontos onde uma leitura purista estaria errada para este domínio:

- **Cadeias fluentes (regra 5):** permitidas e encorajadas — retornos do mesmo tipo não são violação de Deméter.
- **`toArray()` (regra 9):** a única fronteira de serialização sancionada; toda outra leitura de internos foi removida.

Todo o resto em `src/` segue as regras como escritas. Se você usa um assistente de IA para escrever código com o Maybe, aponte-o para o [`llms.txt`](/llms.txt) — ele codifica essas mesmas regras para o código gerado herdá-las.
