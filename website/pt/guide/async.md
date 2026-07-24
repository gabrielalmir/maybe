# Async

`Async` executa callables PHP concorrentemente em **processos filhos** via `proc_open`. Sem `pcntl`, sem extensões — funciona em Windows, hospedagem compartilhada e PHP 7.4, o que o torna adequado para ambientes legados onde outras soluções async não rodam.

## Uso básico

```php
$result = await(async(static function (): int {
    usleep(100000);
    return 42;
}));
```

## Combinadores

```php
use Maybe\Async\Async;

// Executa tudo, espera todos os resultados (preserva as chaves)
$results = Async::all([
    'users' => async(fn () => fetchUsers()),
    'orders' => async(fn () => fetchOrders()),
]);

// O primeiro a terminar vence
$fastest = Async::race([$a, $b, $c]);

// No máximo $limit processos por vez
$results = Async::pool($tasks, 4);
```

## Futures

```php
$future = async(static fn (): int => heavyComputation(), [], ['timeout' => 2.5]);

$future
    ->then(fn ($value) => log_info("pronto: $value"))
    ->catch(fn ($error) => log_error($error))
    ->finally(fn () => cleanup());

$value = $future->resolve();   // bloqueia até terminar (ou timeout)
$future->pending();            // checagem não bloqueante
$future->cancel();             // mata o processo filho
```

## Limitações

Como as tasks rodam em processos separados:

- **Sem memória compartilhada** — tasks recebem argumentos serializados e retornam resultados serializados.
- **Recursos não serializáveis** (conexões de BD, file handles) precisam ser recriados dentro da task.
- **Overhead de spawn** — cada task paga o custo de iniciar um processo PHP; agrupe unidades pequenas de trabalho.

Leia o [Guia de Segurança do Async](https://github.com/gabrielalmir/maybe/blob/main/docs/07-async-safety-guide.md) antes de usar Async em produção.
