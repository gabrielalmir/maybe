<?php

declare(strict_types=1);

namespace Maybe\Async;

use Maybe\Async\Exception\CancelledException;
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
    private $workerFile;

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
    public function __construct($process, array $pipes, string $inputFile, string $outputFile, string $workerFile, ?float $timeoutSeconds, int $pollIntervalMicros)
    {
        $this->process = $process;
        $this->pipes = $pipes;
        $this->inputFile = $inputFile;
        $this->outputFile = $outputFile;
        $this->workerFile = $workerFile;
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
        $this->thenCallbacks[] = $callback;

        return $this;
    }

    public function catch(callable $callback): self
    {
        $this->catchCallbacks[] = $callback;

        return $this;
    }

    public function finally(callable $callback): self
    {
        $this->finallyCallbacks[] = $callback;

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
        if ($status['running'] === false) {
            $this->collectOutcome();
        }

        return !$this->settled;
    }

    public function cancel(): void
    {
        if ($this->settled) {
            return;
        }

        $status = proc_get_status($this->process);
        if ($status['running'] === true) {
            proc_terminate($this->process);
        }

        $this->rawError = new CancelledException('Async task was cancelled');
        $this->settled = true;

        $this->cleanupProcess();
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

        if ($error === null) {
            foreach ($this->thenCallbacks as $callback) {
                try {
                    $value = $callback($value);
                } catch (Throwable $e) {
                    $error = $e;
                    break;
                }
            }
        }

        if ($error !== null) {
            foreach ($this->catchCallbacks as $callback) {
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

        foreach ($this->finallyCallbacks as $callback) {
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
            if ($status['running'] === false) {
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

        $status = proc_get_status($this->process);
        if ($status['running'] === true) {
            proc_terminate($this->process);
        }

        $this->rawError = new TimeoutException('Async task exceeded timeout of ' . $this->timeoutSeconds . ' second(s)');
        $this->settled = true;

        $this->cleanupProcess();
        $this->cleanupFiles();
    }

    private function collectOutcome(): void
    {
        if ($this->settled) {
            return;
        }

        $stderr = $this->readPipe(2);
        $this->cleanupProcess();

        if (!is_file($this->outputFile)) {
            $message = 'Async task finished without output file';
            if ($stderr !== '') {
                $message .= ': ' . trim($stderr);
            }

            $this->rawError = new TaskFailedException($message);
            $this->settled = true;
            $this->cleanupFiles();

            return;
        }

        $json = file_get_contents($this->outputFile);
        if ($json === false || $json === '') {
            $this->rawError = new TaskFailedException('Async task returned empty output');
            $this->settled = true;
            $this->cleanupFiles();

            return;
        }

        /** @var array{ok:bool,result?:string,error?:array{class:string,message:string,code:int,trace:string}}|null $decoded */
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || !array_key_exists('ok', $decoded)) {
            $this->rawError = new TaskFailedException('Async task returned invalid JSON output');
            $this->settled = true;
            $this->cleanupFiles();

            return;
        }

        if ($decoded['ok'] === true) {
            $serialized = isset($decoded['result']) ? base64_decode((string) $decoded['result'], true) : false;
            if ($serialized === false) {
                $this->rawError = new TaskFailedException('Async task returned invalid encoded result');
            } else {
                $this->rawValue = unserialize($serialized);
            }
        } else {
            /** @var array<string,mixed> $error */
            $error = isset($decoded['error']) ? (array) $decoded['error'] : [];
            $message = isset($error['message']) ? (string) $error['message'] : 'Unknown async task error';
            $class = isset($error['class']) ? (string) $error['class'] : 'RuntimeException';
            $code = isset($error['code']) ? (int) $error['code'] : 1;
            $trace = isset($error['trace']) ? (string) $error['trace'] : '';

            $this->rawError = new TaskFailedException($message, $class, $code, $trace);
        }

        $this->settled = true;
        $this->cleanupFiles();
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
        foreach ([$this->inputFile, $this->outputFile, $this->workerFile] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function readPipe(int $index): string
    {
        if (!isset($this->pipes[$index]) || !is_resource($this->pipes[$index])) {
            return '';
        }

        $content = stream_get_contents($this->pipes[$index]);

        return $content === false ? '' : $content;
    }
}
