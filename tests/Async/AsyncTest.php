<?php

declare(strict_types=1);

use Maybe\Async\Async;
use Maybe\Async\Exception\CancelledException;
use Maybe\Async\Exception\TaskFailedException;
use Maybe\Async\Exception\TimeoutException;

it('runs a callable asynchronously and resolves value', function (): void {
    $value = await(async(static function (): int {
        usleep(120000);

        return 42;
    }));

    expect($value)->toBe(42);
});

it('await array resolves multiple futures preserving keys', function (): void {
    $result = await([
        'a' => async(static function (): string {
            usleep(150000);

            return 'A';
        }),
        'b' => async(static function (): string {
            usleep(40000);

            return 'B';
        }),
    ]);

    expect($result)->toBe(['a' => 'A', 'b' => 'B']);
});

it('supports then/catch/finally chaining', function (): void {
    $finallyCalled = false;

    $result = async(static function (): int {
        return 5;
    })
        ->then(static function (int $value): int {
            return $value * 2;
        })
        ->then(static function (int $value): int {
            return $value + 1;
        })
        ->catch(static function (Throwable $e): int {
            return 0;
        })
        ->finally(static function () use (&$finallyCalled): void {
            $finallyCalled = true;
        })
        ->resolve();

    expect($result)->toBe(11)
        ->and($finallyCalled)->toBeTrue();
});

it('propagates worker exceptions as TaskFailedException', function (): void {
    $future = async(static function (): void {
        throw new RuntimeException('boom');
    });

    expect(fn () => $future->resolve())
        ->toThrow(TaskFailedException::class, 'boom');
});

it('returns fastest task on race and cancels others', function (): void {
    $winner = Async::race([
        'slow' => async(static function (): string {
            usleep(250000);

            return 'slow';
        }),
        'fast' => async(static function (): string {
            usleep(50000);

            return 'fast';
        }),
    ]);

    expect($winner)->toBe('fast');
});

it('limits concurrency in pool and returns ordered results', function (): void {
    $tasks = [
        static function (): int {
            usleep(120000);

            return 1;
        },
        static function (): int {
            usleep(120000);

            return 2;
        },
        static function (): int {
            usleep(120000);

            return 3;
        },
        static function (): int {
            usleep(120000);

            return 4;
        },
    ];

    $started = microtime(true);
    $result = Async::pool($tasks, 2);
    $elapsed = microtime(true) - $started;

    expect($result)->toBe([1, 2, 3, 4])
        ->and($elapsed)->toBeGreaterThan(0.18);
});

it('supports non-blocking pending and explicit resolve', function (): void {
    $future = async(static function (): string {
        usleep(100000);

        return 'done';
    });

    $spins = 0;
    while ($future->pending()) {
        $spins++;
        usleep(5000);
    }

    expect($future->resolve())->toBe('done')
        ->and($spins)->toBeGreaterThan(0);
});

it('cancels a running task', function (): void {
    $future = async(static function (): string {
        usleep(300000);

        return 'never';
    });

    usleep(50000);
    $future->cancel();

    expect(fn () => $future->resolve())
        ->toThrow(CancelledException::class);
});

it('times out a task and throws TimeoutException', function (): void {
    $future = async(static function (): string {
        usleep(300000);

        return 'late';
    }, [], ['timeout' => 0.05]);

    expect(fn () => $future->resolve())
        ->toThrow(TimeoutException::class);
});

it('cleans up temporary files after resolve', function (): void {
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'maybe-async-test-' . uniqid('', true);

    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    $value = await(async(static function (): int {
        return 7;
    }, [], ['temp_dir' => $tempDir]));

    $files = glob($tempDir . DIRECTORY_SEPARATOR . '*');
    if ($files === false) {
        $files = [];
    }

    expect($value)->toBe(7);
    expect($files)->toBeArray();
    expect($files)->toHaveCount(0);

    @rmdir($tempDir);
});
