<?php

declare(strict_types=1);

namespace Maybe\Async;

use Maybe\Async\Exception\CancelledException;
use Maybe\Async\Exception\PayloadTooLargeException;
use Maybe\Async\Exception\TaskFailedException;
use Maybe\Async\Exception\TimeoutException;
use RuntimeException;
use Throwable;

class AsyncFuture
{
    /** @var resource */
    private $process;

    /** @var array<int,resource> */
    private $pipes;

    /** @var string */
    private $inputFile;

    /** @var string */
    private $outputFile;

    /** @var string */
    private $secret;

    /** @var string|null */
    private $runDir;

    /** @var int|null */
    private $maxOutputBytes;

    /** @var float|null */
    private $timeoutSeconds;

    /** @var int */
    private $pollIntervalMicros;

    /** @var float */
    private $startedAt;

    /** @var bool */
    private $settled = false;

    /** @var bool */
    private $cleaned = false;

    /** @var bool */
    private $finalized = false;

    /** @var mixed */
    private $rawValue;

    /** @var Throwable|null */
    private $rawError;

    /** @var mixed */
    private $finalValue;

    /** @var Throwable|null */
    private $finalError;

    /** @var array<int,callable> */
    private $thenCallbacks = [];

    /** @var array<int,callable> */
    private $catchCallbacks = [];

    /** @var array<int,callable> */
    private $finallyCallbacks = [];

    /**
     * @param resource $process
     * @param array<int,resource> $pipes
     */
    public function __construct($process, array $pipes, string $inputFile, string $outputFile, ?float $timeoutSeconds, int $pollIntervalMicros, ?string $runDir = null, ?string $secret = null, ?int $maxOutputBytes = null)
    {
        $this->process = $process;
        $this->pipes = $pipes;
        $this->inputFile = $inputFile;
        $this->outputFile = $outputFile;
        $this->secret = $secret ?? '';
        $this->runDir = $runDir;
        $this->maxOutputBytes = $maxOutputBytes;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->pollIntervalMicros = $pollIntervalMicros > 0 ? $pollIntervalMicros : 10000;
        $this->startedAt = microtime(true);
    }

    public function __destruct()
    {
        if (!$this->settled) {
            $this->cancel();
        }

        $this->cleanupFiles();
    }

    public function then(callable $callback): self
    {
        if (!$this->finalized) {
            $this->thenCallbacks[] = $callback;
        }

        return $this;
    }

    public function catch(callable $callback): self
    {
        if (!$this->finalized) {
            $this->catchCallbacks[] = $callback;
        }

        return $this;
    }

    public function finally(callable $callback): self
    {
        if (!$this->finalized) {
            $this->finallyCallbacks[] = $callback;
        }

        return $this;
    }

    public function pending(): bool
    {
        if ($this->settled) {
            return false;
        }

        $this->enforceTimeout();
        if ($this->settled) {
            return false;
        }

        $status = proc_get_status($this->process);
        if (!is_array($status) || $status['running'] === false) {
            $this->collectOutcome();
        }

        return !$this->settled;
    }

    public function cancel(): void
    {
        if ($this->settled) {
            return;
        }

        $this->terminateProcess();
        $this->rawError = new CancelledException('Async task was cancelled');
        $this->settled = true;
        $this->cleanupFiles();
    }

    /** @return mixed */
    public function resolve()
    {
        if ($this->finalized) {
            if ($this->finalError !== null) {
                throw $this->finalError;
            }

            return $this->finalValue;
        }

        if (!$this->settled) {
            $this->waitUntilSettled();
        }

        $value = $this->rawValue;
        $error = $this->rawError;
        $thenCallbacks = $this->thenCallbacks;
        $catchCallbacks = $this->catchCallbacks;
        $finallyCallbacks = $this->finallyCallbacks;
        $this->thenCallbacks = [];
        $this->catchCallbacks = [];
        $this->finallyCallbacks = [];

        if ($error === null) {
            foreach ($thenCallbacks as $callback) {
                try {
                    $value = $callback($value);
                } catch (Throwable $e) {
                    $error = $e;
                    break;
                }
            }
        }

        if ($error !== null) {
            foreach ($catchCallbacks as $callback) {
                try {
                    $candidate = $callback($error);
                } catch (Throwable $e) {
                    $error = $e;
                    continue;
                }

                if ($candidate instanceof Throwable) {
                    $error = $candidate;
                    continue;
                }

                $error = null;
                $value = $candidate;
                break;
            }
        }

        foreach ($finallyCallbacks as $callback) {
            try {
                $callback();
            } catch (Throwable $e) {
                if ($error === null) {
                    $error = $e;
                }
            }
        }

        $this->finalized = true;
        $this->finalValue = $value;
        $this->finalError = $error;
        $this->thenCallbacks = [];
        $this->catchCallbacks = [];
        $this->finallyCallbacks = [];
        $this->rawValue = null;
        $this->rawError = null;
        $this->secret = '';

        if ($error !== null) {
            throw $error;
        }

        return $value;
    }

    private function waitUntilSettled(): void
    {
        while (!$this->settled) {
            $this->enforceTimeout();
            if ($this->settled) {
                break;
            }

            $status = proc_get_status($this->process);
            if (!is_array($status) || $status['running'] === false) {
                $this->collectOutcome();
                break;
            }

            usleep($this->pollIntervalMicros);
        }
    }

    private function enforceTimeout(): void
    {
        if ($this->timeoutSeconds === null || $this->settled) {
            return;
        }

        if ((microtime(true) - $this->startedAt) <= $this->timeoutSeconds) {
            return;
        }

        $this->terminateProcess();
        $this->rawError = new TimeoutException('Async task exceeded timeout of ' . $this->timeoutSeconds . ' second(s)');
        $this->settled = true;
        $this->cleanupFiles();
    }

    private function collectOutcome(): void
    {
        if ($this->settled) {
            return;
        }

        $this->cleanupProcess();
        if (!is_file($this->outputFile)) {
            $this->rawError = new TaskFailedException('Async task finished without output file');
            $this->settled = true;
            $this->cleanupFiles();

            return;
        }

        $size = @filesize($this->outputFile);
        if ($this->maxOutputBytes !== null && $size !== false && $size > $this->maxOutputBytes) {
            $this->rawError = new PayloadTooLargeException('output', $this->maxOutputBytes);
            $this->settled = true;
            $this->cleanupFiles();

            return;
        }

        $encoded = file_get_contents($this->outputFile);
        if ($encoded === false || $encoded === '') {
            $this->rawError = new TaskFailedException('Async task returned empty output');
            $this->settled = true;
            $this->cleanupFiles();

            return;
        }

        try {
            $decodedValue = Ipc::decode($encoded, $this->secret);
            if (!is_array($decodedValue) || !array_key_exists('ok', $decodedValue)) {
                throw new RuntimeException('Async task returned invalid output envelope');
            }

            /** @var array{ok:bool,result?:mixed,error?:array<string,mixed>} $decoded */
            $decoded = $decodedValue;

            if ($decoded['ok'] === true) {
                $this->rawValue = $decoded['result'] ?? null;
            } else {
                $error = isset($decoded['error']) ? $decoded['error'] : [];
                $message = isset($error['message']) ? (string) $error['message'] : 'Unknown async task error';
                $class = isset($error['class']) ? (string) $error['class'] : 'RuntimeException';
                $code = isset($error['code']) ? (int) $error['code'] : 1;
                $trace = isset($error['trace']) ? (string) $error['trace'] : '';
                $direction = isset($error['direction']) ? (string) $error['direction'] : '';
                $limit = isset($error['limit']) ? (int) $error['limit'] : 0;

                $this->rawError = $class === PayloadTooLargeException::class && $direction !== '' && $limit > 0
                    ? new PayloadTooLargeException($direction, $limit)
                    : new TaskFailedException($message, $class, $code, $trace);
            }
        } catch (Throwable $e) {
            $this->rawError = $e instanceof TaskFailedException ? $e : new TaskFailedException($e->getMessage());
        }

        $this->settled = true;
        $this->cleanupFiles();
    }

    private function terminateProcess(): void
    {
        if ($this->cleaned) {
            return;
        }

        $status = proc_get_status($this->process);
        if (is_array($status) && $status['running'] === true) {
            @proc_terminate($this->process);
            $deadline = microtime(true) + 0.1;
            do {
                usleep(10000);
                $status = proc_get_status($this->process);
            } while (is_array($status) && $status['running'] === true && microtime(true) < $deadline);

            if (PHP_OS_FAMILY !== 'Windows' && is_array($status) && $status['running'] === true) {
                @proc_terminate($this->process, 9);
            }
        }

        $this->cleanupProcess();
    }

    private function cleanupProcess(): void
    {
        if ($this->cleaned) {
            return;
        }

        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        if (is_resource($this->process)) {
            @proc_close($this->process);
        }

        $this->cleaned = true;
    }

    private function cleanupFiles(): void
    {
        foreach ([$this->inputFile, $this->outputFile, $this->outputFile . '.tmp'] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        if ($this->runDir !== null && is_dir($this->runDir)) {
            @rmdir($this->runDir);
        }
    }
}
