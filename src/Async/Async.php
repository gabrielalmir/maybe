<?php

declare(strict_types=1);

namespace Maybe\Async;

use Closure;
use Maybe\Async\Exception\PayloadTooLargeException;
use Maybe\Async\Exception\TaskFailedException;
use RuntimeException;

class Async
{
    /** @var int */
    private static $defaultPollIntervalMicros = 10000;

    /** @var string|null */
    private static $defaultTempDir;

    /** @var float|null */
    private static $defaultTimeoutSeconds;

    /** @var int|null */
    private static $defaultMaxInputBytes = 16777216;

    /** @var int|null */
    private static $defaultMaxOutputBytes = 67108864;

    /**
     * @param array<int,mixed> $args
     * @param array<string,mixed> $options
     */
    public static function run(callable $task, array $args = [], array $options = []): AsyncFuture
    {
        $tempDir = array_key_exists('temp_dir', $options) ? (string) $options['temp_dir'] : self::tempDir();
        $timeout = array_key_exists('timeout', $options)
            ? ($options['timeout'] === null ? null : (float) $options['timeout'])
            : self::$defaultTimeoutSeconds;
        if ($timeout !== null && $timeout < 0) {
            throw new RuntimeException('Async timeout must be >= 0');
        }

        $secret = self::makeSecret();
        $maxInputBytes = self::limitOption($options, 'max_input_bytes', self::$defaultMaxInputBytes);
        $maxOutputBytes = self::limitOption($options, 'max_output_bytes', self::$defaultMaxOutputBytes);
        $runDir = self::createRunDir($tempDir);
        $inputFile = $runDir . DIRECTORY_SEPARATOR . 'input.bin';
        $outputFile = $runDir . DIRECTORY_SEPARATOR . 'output.bin';
        $process = null;

        try {
            $payloadData = [
                'kind' => 'callable',
                'task' => '',
                'args' => serialize($args),
                'include_remote_trace' => !empty($options['include_remote_trace']),
            ];

            if ($task instanceof Closure) {
                self::ensureOpisClosureLoaded();
                $payloadData['kind'] = 'closure';
                $payloadData['task'] = \Opis\Closure\serialize($task);
            } else {
                $payloadData['task'] = serialize($task);
            }

            $payload = Ipc::encode($payloadData, $secret);
            if ($maxInputBytes !== null && strlen($payload) > $maxInputBytes) {
                throw new PayloadTooLargeException('input', $maxInputBytes);
            }

            self::writePrivateFile($inputFile, $payload);

            $phpBinary = isset($options['php_binary']) ? (string) $options['php_binary'] : PHP_BINARY;
            $autoload = isset($options['autoload']) ? (string) $options['autoload'] : self::resolveAutoloadPath();
            $workerFile = __DIR__ . DIRECTORY_SEPARATOR . 'worker.php';
            $command = [$phpBinary, $workerFile, $inputFile, $outputFile, $autoload ?? '', $maxOutputBytes === null ? '' : (string) $maxOutputBytes];
            $nullDevice = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['file', $nullDevice, 'ab'],
                2 => ['file', $nullDevice, 'ab'],
            ];

            $pipes = [];
            $process = proc_open($command, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
            if (!is_resource($process)) {
                throw new RuntimeException('Failed to start async process');
            }

            if (isset($pipes[0]) && is_resource($pipes[0])) {
                if (fwrite($pipes[0], $secret) !== strlen($secret)) {
                    fclose($pipes[0]);
                    throw new RuntimeException('Failed to initialize async worker');
                }
                fclose($pipes[0]);
                unset($pipes[0]);
            }

            $pollInterval = isset($options['poll_interval'])
                ? (int) $options['poll_interval']
                : self::$defaultPollIntervalMicros;

            return new AsyncFuture($process, $pipes, $inputFile, $outputFile, $timeout, $pollInterval, $runDir, $secret, $maxOutputBytes);
        } catch (\Throwable $e) {
            if (is_resource($process)) {
                @proc_terminate($process);
                @proc_close($process);
            }

            self::removeRunDir($runDir);
            throw $e;
        }
    }

    /**
     * @param array<mixed,AsyncFuture> $futures
     * @return array<mixed,mixed>
     */
    public static function all(array $futures): array
    {
        $results = [];
        try {
            foreach ($futures as $key => $future) {
                if (!$future instanceof AsyncFuture) {
                    $results[$key] = $future;
                    continue;
                }

                $results[$key] = $future->resolve();
            }
        } catch (\Throwable $e) {
            foreach ($futures as $future) {
                if ($future instanceof AsyncFuture) {
                    $future->cancel();
                }
            }

            throw $e;
        }

        return $results;
    }

    /**
     * @param array<mixed,mixed> $futures
     * @return mixed
     */
    public static function race(array $futures)
    {
        if ($futures === []) {
            throw new RuntimeException('Async::race expects at least one future');
        }

        try {
            while (true) {
                foreach ($futures as $key => $future) {
                    if (!$future instanceof AsyncFuture) {
                        self::cancelOthers($futures, $key);

                        return $future;
                    }

                    if (!$future->pending()) {
                        $winner = $future->resolve();
                        self::cancelOthers($futures, $key);

                        return $winner;
                    }
                }

                usleep(self::$defaultPollIntervalMicros);
            }
        } catch (\Throwable $e) {
            foreach ($futures as $future) {
                if ($future instanceof AsyncFuture) {
                    $future->cancel();
                }
            }

            throw $e;
        }
    }

    /**
     * @param array<mixed,mixed> $tasks
     * @param array<string,mixed> $options
     * @return array<mixed,mixed>
     */
    public static function pool(array $tasks, int $limit = 5, array $options = []): array
    {
        if ($limit < 1) {
            throw new RuntimeException('Pool limit must be >= 1');
        }

        $keys = array_keys($tasks);
        $cursor = 0;
        $running = [];
        $results = [];
        $pollInterval = isset($options['poll_interval']) ? (int) $options['poll_interval'] : self::$defaultPollIntervalMicros;

        try {
            while ($cursor < count($keys) || $running !== []) {
                while ($cursor < count($keys) && count($running) < $limit) {
                    $key = $keys[$cursor];
                    $running[$key] = self::toFuture($tasks[$key], $options);
                    $cursor++;
                }

                foreach ($running as $key => $future) {
                    if ($future->pending()) {
                        continue;
                    }

                    $results[$key] = $future->resolve();
                    unset($running[$key]);
                }

                if ($running !== []) {
                    usleep($pollInterval);
                }
            }
        } catch (\Throwable $e) {
            foreach ($running as $future) {
                $future->cancel();
            }

            throw $e;
        }

        $ordered = [];
        foreach ($keys as $key) {
            $ordered[$key] = $results[$key];
        }

        return $ordered;
    }

    public static function setDefaultTempDir(string $tempDir): void
    {
        self::$defaultTempDir = $tempDir;
    }

    public static function setDefaultTimeout(?float $seconds): void
    {
        if ($seconds !== null && $seconds < 0) {
            throw new RuntimeException('Async timeout must be >= 0');
        }

        self::$defaultTimeoutSeconds = $seconds;
    }

    public static function setDefaultPollInterval(int $microseconds): void
    {
        self::$defaultPollIntervalMicros = $microseconds > 0 ? $microseconds : 10000;
    }

    public static function setDefaultMaxInputBytes(?int $bytes): void
    {
        self::$defaultMaxInputBytes = self::validateLimit($bytes);
    }

    public static function setDefaultMaxOutputBytes(?int $bytes): void
    {
        self::$defaultMaxOutputBytes = self::validateLimit($bytes);
    }

    private static function tempDir(): string
    {
        return self::$defaultTempDir ?? sys_get_temp_dir();
    }

    private static function makeSecret(): string
    {
        return random_bytes(32);
    }

    private static function createRunDir(string $tempDir): string
    {
        if (is_link($tempDir)) {
            throw new RuntimeException('Async temp dir must not be a symlink');
        }

        if (!is_dir($tempDir) && !@mkdir($tempDir, 0700, true) && !is_dir($tempDir)) {
            throw new RuntimeException('Failed to create async temp dir: ' . $tempDir);
        }

        $baseDir = realpath($tempDir);
        if ($baseDir === false || !is_dir($baseDir)) {
            throw new RuntimeException('Async temp dir could not be resolved safely');
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            $permissions = @fileperms($baseDir);
            if ($permissions !== false && ($permissions & 0002) !== 0 && ($permissions & 01000) === 0) {
                throw new RuntimeException('Async temp dir is world-writable without a sticky bit');
            }
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $runDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16));
            if (@mkdir($runDir, 0700)) {
                return $runDir;
            }
        }

        throw new RuntimeException('Failed to create isolated async run dir');
    }

    private static function writePrivateFile(string $path, string $contents): void
    {
        $handle = @fopen($path, 'xb');
        if ($handle === false) {
            throw new RuntimeException('Failed to create async input file: ' . $path);
        }

        try {
            $written = 0;
            $length = strlen($contents);
            while ($written < $length) {
                $chunk = fwrite($handle, substr($contents, $written));
                if ($chunk === false || $chunk === 0) {
                    throw new RuntimeException('Failed to write async input file: ' . $path);
                }

                $written += $chunk;
            }
        } finally {
            fclose($handle);
        }

        @chmod($path, 0600);
    }

    private static function removeRunDir(string $runDir): void
    {
        foreach (glob($runDir . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            if (is_file($path) || is_link($path)) {
                @unlink($path);
            }
        }

        @rmdir($runDir);
    }

    /**
     * @param array<mixed,mixed> $futures
     * @param int|string $winnerKey
     */
    private static function cancelOthers(array $futures, $winnerKey): void
    {
        foreach ($futures as $key => $future) {
            if ($key !== $winnerKey && $future instanceof AsyncFuture) {
                $future->cancel();
            }
        }
    }

    /** @param array<string,mixed> $options */
    private static function limitOption(array $options, string $key, ?int $default): ?int
    {
        return array_key_exists($key, $options) ? self::validateLimit($options[$key]) : $default;
    }

    /** @param mixed $bytes */
    private static function validateLimit($bytes): ?int
    {
        if ($bytes === null) {
            return null;
        }

        $value = (int) $bytes;
        if ($value < 1) {
            throw new \InvalidArgumentException('Async payload limits must be null or >= 1');
        }

        return $value;
    }

    private static function resolveAutoloadPath(): ?string
    {
        foreach (get_included_files() as $file) {
            $normalized = str_replace('\\', '/', $file);
            if (substr($normalized, -19) === 'vendor/autoload.php') {
                return $file;
            }
        }

        return null;
    }

    private static function ensureOpisClosureLoaded(): void
    {
        if (function_exists('\\Opis\\Closure\\serialize')) {
            return;
        }

        $candidates = [
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'opis' . DIRECTORY_SEPARATOR . 'closure' . DIRECTORY_SEPARATOR . 'autoload.php',
            dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'opis' . DIRECTORY_SEPARATOR . 'closure' . DIRECTORY_SEPARATOR . 'autoload.php',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                require_once $path;
                break;
            }
        }
    }

    /**
     * @param mixed $task
     * @param array<string,mixed> $options
     */
    private static function toFuture($task, array $options): AsyncFuture
    {
        if ($task instanceof AsyncFuture) {
            return $task;
        }

        if (is_callable($task)) {
            return self::run($task, [], $options);
        }

        if (is_array($task) && isset($task[0]) && is_callable($task[0])) {
            $callable = $task[0];
            $args = isset($task[1]) && is_array($task[1]) ? $task[1] : [];
            $taskOptions = isset($task[2]) && is_array($task[2]) ? $task[2] : $options;

            return self::run($callable, $args, $taskOptions);
        }

        throw new TaskFailedException('Pool task must be callable, AsyncFuture, or [callable, args, options]');
    }
}
