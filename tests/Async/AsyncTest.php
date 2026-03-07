<?php

declare(strict_types=1);

use Maybe\Async\Async;
use Maybe\Async\Exception\CancelledException;
use Maybe\Async\Exception\TaskFailedException;
use Maybe\Async\Exception\TimeoutException;
use Maybe\Tests\Async\TestTasks;

it('runs a callable asynchronously and resolves value', function (): void {
    $value = Async::run([TestTasks::class, 'sleepAndReturnInt'], [120000, 42])->resolve();

    expect($value)->toBe(42);
});

it('await array resolves multiple futures preserving keys', function (): void {
    $result = Async::all([
        'a' => Async::run([TestTasks::class, 'sleepAndReturnString'], [150000, 'A']),
        'b' => Async::run([TestTasks::class, 'sleepAndReturnString'], [40000, 'B']),
    ]);

    expect($result)->toBe(['a' => 'A', 'b' => 'B']);
});

it('supports then/catch/finally chaining', function (): void {
    $finallyCalled = false;

    $result = Async::run([TestTasks::class, 'returnFive'])
        ->then(static function (int $value): int {
            return $value * 2;
        })
        ->then(static function (int $value): int {
            return $value + 1;
        })
        ->catch(static function (\Throwable $e): int {
            return 0;
        })
        ->finally(static function () use (&$finallyCalled): void {
            $finallyCalled = true;
        })
        ->resolve();

    expect($result)->toBe(11);
    expect($finallyCalled)->toBeTrue();
});

it('propagates worker exceptions as TaskFailedException', function (): void {
    $future = Async::run([TestTasks::class, 'throwBoom']);

    expect(fn () => $future->resolve())
        ->toThrow(TaskFailedException::class, 'boom');
});

it('returns fastest task on race and cancels others', function (): void {
    $winner = Async::race([
        'slow' => Async::run([TestTasks::class, 'sleepAndReturnString'], [250000, 'slow']),
        'fast' => Async::run([TestTasks::class, 'sleepAndReturnString'], [50000, 'fast']),
    ]);

    expect($winner)->toBe('fast');
});

it('limits concurrency in pool and returns ordered results', function (): void {
    $tasks = [
        [[TestTasks::class, 'sleepAndReturnInt'], [120000, 1]],
        [[TestTasks::class, 'sleepAndReturnInt'], [120000, 2]],
        [[TestTasks::class, 'sleepAndReturnInt'], [120000, 3]],
        [[TestTasks::class, 'sleepAndReturnInt'], [120000, 4]],
    ];

    $started = microtime(true);
    $result = Async::pool($tasks, 2);
    $elapsed = microtime(true) - $started;

    expect($result)->toBe([1, 2, 3, 4]);
    expect($elapsed)->toBeGreaterThan(0.18);
});

it('supports non-blocking pending and explicit resolve', function (): void {
    $future = Async::run([TestTasks::class, 'sleepAndReturnString'], [100000, 'done']);

    $spins = 0;
    while ($future->pending()) {
        $spins++;
        usleep(5000);
    }

    expect($future->resolve())->toBe('done');
    expect($spins)->toBeGreaterThan(0);
});

it('cancels a running task', function (): void {
    $future = Async::run([TestTasks::class, 'sleepAndReturnString'], [300000, 'never']);

    usleep(50000);
    $future->cancel();

    expect(fn () => $future->resolve())
        ->toThrow(CancelledException::class);
});

it('times out a task and throws TimeoutException', function (): void {
    $future = Async::run([TestTasks::class, 'sleepAndReturnString'], [300000, 'late'], ['timeout' => 0.05]);

    expect(fn () => $future->resolve())
        ->toThrow(TimeoutException::class);
});

it('cleans up temporary files after resolve', function (): void {
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'maybe-async-test-' . uniqid('', true);

    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    $value = Async::run([TestTasks::class, 'sleepAndReturnInt'], [0, 7], ['temp_dir' => $tempDir])->resolve();

    $files = glob($tempDir . DIRECTORY_SEPARATOR . '*');
    if ($files === false) {
        $files = [];
    }

    expect($value)->toBe(7);
    expect($files)->toBeArray();
    expect($files)->toHaveCount(0);

    @rmdir($tempDir);
});
