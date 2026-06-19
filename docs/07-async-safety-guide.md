# Async Safety Guide

`Async` is powerful, but it should not be the first adoption path. New teams should start with `Schema`, `DTO`, and `Result`, then evaluate `Async` only for independent work where process overhead and operational complexity are justified.

## Process Isolation

Maybe `Async` runs tasks in separate PHP processes. A child process does not share normal PHP memory with the parent process.

This means:

- Changes in the child process do not mutate parent variables.
- Parent objects are not live shared objects inside the child.
- External resources must be opened or recreated in the child when needed.

## No Shared Memory Warning

Do not assume arrays, objects, caches, or service containers are shared between parent and child processes. Pass only the data needed by the task, and return a serializable result.

## No Shared Database Connection Warning

Do not share database connections between parent and child processes. Create a new connection inside the child process if database access is truly required.

Unsafe:

```php
$db = $this->db;

$future = async(static function () use ($db) {
    return $db->get('customers')->result_array();
});
```

Safer:

```php
$future = async(static function () {
    require __DIR__ . '/../vendor/autoload.php';
    $pdo = new PDO('mysql:host=localhost;dbname=app', 'user', 'pass');

    return $pdo->query('select id, email from customers')->fetchAll(PDO::FETCH_ASSOC);
}, array(), array('timeout' => 5.0));
```

## Serialization Limitations

Tasks and task arguments must be serializable. Avoid passing:

- Open database connections.
- File handles.
- Framework controllers.
- Service containers.
- Closures that capture large object graphs.

Prefer passing scalar IDs, arrays, and simple values.

## Timeout Recommendations

Use timeouts when work may block on external systems:

```php
$future = async(static function (string $url): string {
    return file_get_contents($url);
}, array('https://example.com/status'), array('timeout' => 3.0));
```

Document what should happen if a timeout occurs: retry, show partial results, log and continue, or fail the request.

## When to Use Async

Use `Async` for:

- Independent external calls.
- Controlled parallel processing.
- Expensive read-only calculations.
- Non-critical background-style workloads where process overhead is acceptable.

## When Not to Use Async

Avoid `Async` for:

- Trivial tasks.
- Work that must share a transaction.
- Work that depends on request-scoped framework state.
- Tasks requiring shared mutable memory.
- Critical operations without timeout or failure handling.

## Safe Example

```php
$customerId = 123;

$future = async(static function (int $id): array {
    return array('customer_id' => $id, 'score' => 95);
}, array($customerId), array('timeout' => 2.0));

$result = await($future);
```

## Unsafe Example

```php
$future = async(function () {
    return $this->db->get('customers')->result_array();
});
```

This captures framework state and a database connection. Recreate required resources inside the child process instead.

## CodeIgniter 3-Specific Considerations

- Do not capture `CI_Controller` instances in async tasks.
- Do not capture `$this->db`, `$this->input`, session objects, or loaded libraries.
- Prefer passing IDs or simple arrays into the task.
- Bootstrap only what the child process needs.
- Treat async work as a separate process with its own lifecycle, logging, and failures.
