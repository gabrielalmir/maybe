<?php

declare(strict_types=1);

namespace Maybe\Async;

use Throwable;

final class WorkerRuntime
{
    public static function run(string $inputFile, string $outputFile, ?string $autoloadFile = null, ?int $maxOutputBytes = null): void
    {
        if ($autoloadFile !== null && $autoloadFile !== '' && is_file($autoloadFile)) {
            require_once $autoloadFile;
        }

        $secret = stream_get_contents(STDIN);
        if ($secret === false || $secret === '') {
            return;
        }

        $includeRemoteTrace = false;
        try {
            $encodedInput = file_get_contents($inputFile);
            if ($encodedInput === false) {
                throw new \RuntimeException('Async input file could not be read');
            }

            $decodedPayload = Ipc::decode($encodedInput, $secret);
            if (!is_array($decodedPayload)) {
                throw new \RuntimeException('Async input envelope must contain an array');
            }

            /** @var array{kind?:string,task:string,args:string,include_remote_trace?:bool} $payload */
            $payload = $decodedPayload;
            $includeRemoteTrace = !empty($payload['include_remote_trace']);

            $kind = isset($payload['kind']) ? (string) $payload['kind'] : 'closure';

            if ($kind === 'closure') {
                self::ensureOpisClosureLoaded();
                /** @var callable $task */
                $task = \Opis\Closure\unserialize($payload['task']);
            } else {
                /** @var mixed $callable */
                $callable = unserialize($payload['task'], ['allowed_classes' => true, 'max_depth' => 4096]);
                if (!is_callable($callable)) {
                    throw new \RuntimeException('Task payload is not callable');
                }

                $task = $callable;
            }

            /** @var array<int,mixed> $args */
            $args = unserialize($payload['args'], ['allowed_classes' => true, 'max_depth' => 4096]);
            if (!is_array($args)) {
                throw new \RuntimeException('Task args payload must be an array');
            }

            $value = $task(...$args);
            $result = [
                'ok' => true,
                'result' => $value,
            ];
        } catch (Throwable $e) {
            $result = [
                'ok' => false,
                'error' => [
                    'class' => get_class($e),
                    'message' => $e->getMessage(),
                    'code' => (int) $e->getCode(),
                    'trace' => $includeRemoteTrace ? $e->getTraceAsString() : '',
                ],
            ];
        }

        try {
            $encoded = Ipc::encode($result, $secret);
        } catch (Throwable $e) {
            $encoded = Ipc::encode([
                'ok' => false,
                'error' => [
                    'class' => get_class($e),
                    'message' => $e->getMessage(),
                    'code' => (int) $e->getCode(),
                    'trace' => $includeRemoteTrace ? $e->getTraceAsString() : '',
                ],
            ], $secret);
        }
        if ($maxOutputBytes !== null && strlen($encoded) > $maxOutputBytes) {
            $encoded = Ipc::encode([
                'ok' => false,
                'error' => [
                    'class' => 'Maybe\\Async\\Exception\\PayloadTooLargeException',
                    'message' => 'Async output payload exceeds the configured limit of ' . $maxOutputBytes . ' bytes',
                    'code' => 0,
                    'trace' => '',
                    'direction' => 'output',
                    'limit' => $maxOutputBytes,
                ],
            ], $secret);
        }

        self::writeAtomically($outputFile, $encoded);
    }

    private static function writeAtomically(string $outputFile, string $contents): void
    {
        $temporaryFile = $outputFile . '.tmp';
        @unlink($temporaryFile);
        if (file_put_contents($temporaryFile, $contents, LOCK_EX) === false) {
            return;
        }

        @chmod($temporaryFile, 0600);
        @rename($temporaryFile, $outputFile);
    }

    private static function ensureOpisClosureLoaded(): void
    {
        if (function_exists('\\Opis\\Closure\\unserialize')) {
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
}
