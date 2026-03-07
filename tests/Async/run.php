<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

if (!function_exists('async')) {
    require __DIR__ . '/../../src/async_helpers.php';
}
if (!class_exists('Async', false)) {
    require __DIR__ . '/../../src/Async.php';
}
if (!class_exists('Async_future', false)) {
    require __DIR__ . '/../../src/Async_future.php';
}

use Maybe\Async\Async;
use Maybe\Async\Exception\CancelledException;
use Maybe\Async\Exception\TaskFailedException;
use Maybe\Async\Exception\TimeoutException;

$tests = [];

$tests['runs a callable asynchronously and resolves value'] = static function (): void {
    $value = await(async(static function (): int {
        usleep(120000);
        return 42;
    }));

    if ($value !== 42) {
        throw new RuntimeException('Expected 42');
    }
};

$tests['await array resolves multiple futures preserving keys'] = static function (): void {
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

    if ($result !== ['a' => 'A', 'b' => 'B']) {
        throw new RuntimeException('Expected keyed array');
    }
};

$tests['supports then/catch/finally chaining'] = static function (): void {
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

    if ($result !== 11 || $finallyCalled !== true) {
        throw new RuntimeException('Expected result 11 and finally called');
    }
};

$tests['propagates worker exceptions as TaskFailedException'] = static function (): void {
    $future = async(static function (): void {
        throw new RuntimeException('boom');
    });

    try {
        $future->resolve();
        throw new RuntimeException('Expected TaskFailedException');
    } catch (TaskFailedException $e) {
        if (strpos($e->getMessage(), 'boom') === false) {
            throw new RuntimeException('Expected message containing boom');
        }
    }
};

$tests['returns fastest task on race and cancels others'] = static function (): void {
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

    if ($winner !== 'fast') {
        throw new RuntimeException('Expected fast winner');
    }
};

$tests['limits concurrency in pool and returns ordered results'] = static function (): void {
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

    if ($result !== [1, 2, 3, 4]) {
        throw new RuntimeException('Expected ordered pool results');
    }

    if ($elapsed < 0.18) {
        throw new RuntimeException('Expected constrained concurrency elapsed >= 0.18s');
    }
};

$tests['supports non-blocking pending and explicit resolve'] = static function (): void {
    $future = async(static function (): string {
        usleep(100000);
        return 'done';
    });

    $spins = 0;
    while ($future->pending()) {
        $spins++;
        usleep(5000);
    }

    if ($future->resolve() !== 'done' || $spins <= 0) {
        throw new RuntimeException('Expected pending loop to spin and resolve done');
    }
};

$tests['cancels a running task'] = static function (): void {
    $future = async(static function (): string {
        usleep(300000);
        return 'never';
    });

    usleep(50000);
    $future->cancel();

    try {
        $future->resolve();
        throw new RuntimeException('Expected CancelledException');
    } catch (CancelledException $e) {
    }
};

$tests['times out a task and throws TimeoutException'] = static function (): void {
    $future = async(static function (): string {
        usleep(300000);
        return 'late';
    }, [], ['timeout' => 0.05]);

    try {
        $future->resolve();
        throw new RuntimeException('Expected TimeoutException');
    } catch (TimeoutException $e) {
    }
};

$tests['cleans up temporary files after resolve'] = static function (): void {
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'maybe-async-test-' . uniqid('', true);

    if (!is_dir($tempDir) && !mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
        throw new RuntimeException('Failed to create temp dir');
    }

    $value = await(async(static function (): int {
        return 7;
    }, [], ['temp_dir' => $tempDir]));

    $files = glob($tempDir . DIRECTORY_SEPARATOR . '*');
    if ($files === false) {
        $files = [];
    }

    if ($value !== 7 || count($files) !== 0) {
        throw new RuntimeException('Expected cleanup of temp files');
    }

    @rmdir($tempDir);
};

$passed = 0;
$failed = 0;

foreach ($tests as $name => $test) {
    try {
        $test();
        $passed++;
        fwrite(STDOUT, "[PASS] {$name}\n");
    } catch (Throwable $e) {
        $failed++;
        fwrite(STDERR, "[FAIL] {$name}: " . $e->getMessage() . "\n");
    }
}

fwrite(STDOUT, "\nAsync tests: {$passed} passed, {$failed} failed\n");
exit($failed === 0 ? 0 : 1);
