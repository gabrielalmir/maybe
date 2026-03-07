# Maybe Async (v0.2.0)

Biblioteca de concorrencia para PHP 7.4 com foco em ambientes legados (Windows + CodeIgniter 3), usando processos filhos via `proc_open()`.

## Requisitos

- PHP `>=7.4`
- Composer
- Sem extensoes PECL adicionais

## Instalacao

```bash
composer require gabrielalmir/maybe
```

## Dependencia

A biblioteca usa apenas:

- `opis/closure` para serializacao de closures

## API publica

- `async(callable $task, array $args = [], array $options = []): Async_future`
- `await(Async_future|array $value)`
- `Async::run()`
- `Async::all()`
- `Async::race()`
- `Async::pool()`

## Uso basico

```php
$resultado = await(async(fn() => calcular()));
```

## Multiplas tasks

```php
$dados = await([
    'usuarios' => async(fn() => buscar_usuarios()),
    'pedidos'  => async(fn() => buscar_pedidos()),
    'estoque'  => async(fn() => buscar_estoque()),
]);
```

## Com argumentos

```php
$quadrado = await(async(fn(int $n) => $n ** 2, [9]));
```

## Encadeamento

```php
$resultado = async(fn() => buscar_dados())
    ->then(fn($dados) => transformar($dados))
    ->then(fn($dados) => enriquecer($dados))
    ->catch(fn($e) => ['erro' => $e->getMessage()])
    ->finally(fn() => fechar_recursos())
    ->resolve();
```

## Race

```php
$vencedor = Async::race([
    'cache' => async(fn() => ler_cache()),
    'db'    => async(fn() => ler_banco()),
]);
```

## Pool

```php
$tasks = [
    fn() => processar(1),
    fn() => processar(2),
    fn() => processar(3),
];

$resultados = Async::pool($tasks, 2);
```

## Non-blocking

```php
$task = async(fn() => tarefa_longa());

while ($task->pending()) {
    // outras tarefas
}

$resultado = $task->resolve();
```

## Cancelamento

```php
$task = async(fn() => tarefa_demorada());
$task->cancel();
```

## Timeout

```php
$task = async(fn() => tarefa_lenta(), [], ['timeout' => 2.5]);
$resultado = $task->resolve();
```

## CodeIgniter 3

Com Composer carregado no projeto CI3, voce pode usar:

```php
$this->load->library('async');

$resultado = await(async(fn() => 123));
```

A classe global `Async` e o tipo `Async_future` sao expostos para compatibilidade.

## Limitacoes

- Processos sao isolados: sem compartilhamento de memoria com o pai
- Recursos nao serializaveis devem ser recriados no filho
- Existe overhead de spawn de processo por task

## Desenvolvimento

```bash
composer test
composer lint
```
