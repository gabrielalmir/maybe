# Async

`Async` ejecuta un callable serializable en un proceso PHP separado. Es útil
para trabajo independiente, pero añade coste de proceso y una frontera de
serialización.

```php
use Maybe\Async\Async;

$future = Async::run(
    static fn (int $id): array => loadReport($id),
    [42],
    ['timeout' => 3.0],
);

$report = $future->resolve();
```

## Seguridad y límites

- La entrada serializada tiene un límite predeterminado de 16 MiB.
- La salida serializada tiene un límite predeterminado de 64 MiB.
- Configura `max_input_bytes` y `max_output_bytes` solo desde configuración confiable; `null` desactiva el límite de forma explícita.
- El IPC usa mensajes autenticados y archivos privados por ejecución.
- stdout y stderr de la tarea se descartan para evitar deadlocks por buffers.
- `include_remote_trace` está desactivado por defecto para no exponer rutas o datos sensibles.

```php
$future = Async::run($task, $args, [
    'timeout' => 5.0,
    'max_output_bytes' => 8 * 1024 * 1024,
    'include_remote_trace' => false,
]);
```

Usa `all()` para esperar todos, `race()` para el primero y `pool()` para limitar
la concurrencia. `cancel()` y los timeouts reaprovechan el worker, pero no
garantizan terminar procesos descendientes creados por la tarea.
