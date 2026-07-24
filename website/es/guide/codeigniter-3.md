# CodeIgniter 3

Maybe no necesita un framework, pero ofrece aliases globales para adopción
gradual en aplicaciones CI3.

```php
$this->load->library('async');

$result = await(async(static function (): int {
    return 123;
}));
```

No captures un controller, conexión de base de datos, sesión o servicio CI3 en
una tarea Async. Pasa IDs y arrays simples y recrea los recursos dentro del
proceso hijo.
