# Async

`Async` runs PHP callables concurrently in **child processes** via `proc_open`. No `pcntl`, no extensions — it works on Windows, shared hosting and PHP 7.4, which makes it a fit for legacy environments where other async solutions can't run.

## Basic usage

```php
$result = await(async(static function (): int {
    usleep(100000);
    return 42;
}));
```

## Combinators

```php
use Maybe\Async\Async;

// Run everything, wait for all results (preserves keys)
$results = Async::all([
    'users' => async(fn () => fetchUsers()),
    'orders' => async(fn () => fetchOrders()),
]);

// First one to finish wins
$fastest = Async::race([$a, $b, $c]);

// At most $limit processes at a time
$results = Async::pool($tasks, 4);
```

## Futures

```php
$future = async(static fn (): int => heavyComputation(), [], ['timeout' => 2.5]);

$future
    ->then(fn ($value) => log_info("done: $value"))
    ->catch(fn ($error) => log_error($error))
    ->finally(fn () => cleanup());

$value = $future->resolve();   // blocks until done (or timeout)
$future->pending();            // non-blocking check
$future->cancel();             // kill the child process
```

## Limitations

Because tasks run in separate processes:

- **No shared memory** — tasks receive serialized arguments and return serialized results.
- **Non-serializable resources** (DB connections, file handles) must be recreated inside the task.
- **Spawn overhead** — each task pays the cost of starting a PHP process; batch small units of work.

Read the [Async Safety Guide](https://github.com/gabrielalmir/maybe/blob/main/docs/07-async-safety-guide.md) before using Async in production.
