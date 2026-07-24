<?php

declare(strict_types=1);

use Maybe\Async\Async;
use Maybe\Async\Exception\PayloadTooLargeException;
use Maybe\Async\Ipc;
use Maybe\Async\Exception\TaskFailedException;
use Maybe\Schema\Schema;
use Maybe\Tests\Async\TestTasks;

it('does not deadlock when a task writes a large stdout payload', function (): void {
    expect(Async::run([TestTasks::class, 'writeLargeStdout'], [1024 * 1024])->resolve())
        ->toBe('done');
});

it('rejects oversized input before starting a worker', function (): void {
    expect(fn () => Async::run([TestTasks::class, 'returnLargeString'], [1024], ['max_input_bytes' => 64]))
        ->toThrow(PayloadTooLargeException::class);
});

it('rejects oversized output in the worker', function (): void {
    expect(fn () => Async::run([TestTasks::class, 'returnLargeString'], [1024 * 1024], ['max_output_bytes' => 1024])->resolve())
        ->toThrow(PayloadTooLargeException::class);
});

it('cancels remaining futures when all fails', function (): void {
    $slow = Async::run([TestTasks::class, 'sleepAndReturnString'], [300000, 'slow']);
    $failed = Async::run([TestTasks::class, 'throwBoom']);

    expect(fn () => Async::all([$failed, $slow]))->toThrow(TaskFailedException::class);
    expect(fn () => $slow->resolve())->toThrow(\Maybe\Async\Exception\CancelledException::class);
});

it('does not expose remote traces unless requested', function (): void {
    try {
        Async::run([TestTasks::class, 'throwBoom'])->resolve();
    } catch (TaskFailedException $e) {
        expect($e->remoteTrace())->toBe('');
    }

    try {
        Async::run([TestTasks::class, 'throwBoom'], [], ['include_remote_trace' => true])->resolve();
    } catch (TaskFailedException $e) {
        expect($e->remoteTrace())->not->toBe('');
    }
});

it('rejects an unsafe world-writable temp directory on POSIX', function (): void {
    if (PHP_OS_FAMILY === 'Windows') {
        return;
    }

    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'maybe-unsafe-' . bin2hex(random_bytes(8));
    mkdir($tempDir, 0700, true);
    chmod($tempDir, 0777);

    expect(fn () => Async::run([TestTasks::class, 'returnLargeString'], [1], ['temp_dir' => $tempDir]))
        ->toThrow(RuntimeException::class);

    chmod($tempDir, 0700);
    @rmdir($tempDir);
});

it('authenticates tampered IPC input', function (): void {
    $encoded = Ipc::encode(['value' => 'safe'], 'secret');
    $tampered = substr($encoded, 0, -1) . 'x';

    expect(fn () => Ipc::decode($tampered, 'secret'))->toThrow(RuntimeException::class);
});

it('keeps validation error collection linear for large invalid arrays', function (): void {
    $input = array_fill(0, 10000, 42);
    $result = Schema::arrayOf(Schema::string())->safeParse($input);

    expect($result->isErr())->toBeTrue();
    expect($result->unwrapErr()->count())->toBe(10000);
});

it('releases settled future callbacks and intermediate values', function (): void {
    $baseline = memory_get_usage(true);

    for ($index = 0; $index < 50; $index++) {
        $future = Async::run([TestTasks::class, 'returnLargeString'], [65536]);
        $captured = str_repeat('c', 512 * 1024);
        $future->then(static function (string $value) use ($captured): string {
            unset($captured);

            return $value;
        })->resolve();
        unset($future);
        unset($captured);

        if ($index % 10 === 0) {
            gc_collect_cycles();
        }
    }

    gc_collect_cycles();
    expect(memory_get_usage(true) - $baseline)->toBeLessThan(16 * 1024 * 1024);
});
