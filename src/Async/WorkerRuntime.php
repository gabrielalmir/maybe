<?php

declare(strict_types=1);

namespace Maybe\Async;

use Opis\Closure\SerializableClosure;
use Throwable;

final class WorkerRuntime
{
    public static function run(string $inputFile, string $outputFile, ?string $autoloadFile = null): void
    {
        if ($autoloadFile !== null && $autoloadFile !== '' && is_file($autoloadFile)) {
            require_once $autoloadFile;
        }

        self::ensureOpisClosureLoaded();

        $result = [
            'ok' => false,
            'error' => [
                'class' => 'RuntimeException',
                'message' => 'Unknown worker failure',
                'code' => 1,
                'trace' => '',
            ],
        ];

        try {
            /** @var array{task:string,args:string} $payload */
            $payload = require $inputFile;

            /** @var mixed $taskPayload */
            $taskPayload = unserialize($payload['task']);
            if (!$taskPayload instanceof SerializableClosure) {
                throw new \RuntimeException('Invalid serialized task payload');
            }

            $task = $taskPayload->getClosure();
            /** @var array<int,mixed> $args */
            $args = unserialize($payload['args']);

            $value = $task(...$args);

            $result = [
                'ok' => true,
                'result' => base64_encode(serialize($value)),
            ];
        } catch (Throwable $e) {
            $result = [
                'ok' => false,
                'error' => [
                    'class' => get_class($e),
                    'message' => $e->getMessage(),
                    'code' => (int) $e->getCode(),
                    'trace' => $e->getTraceAsString(),
                ],
            ];
        }

        $encoded = json_encode($result);
        if ($encoded === false) {
            $encoded = '{"ok":false,"error":{"class":"RuntimeException","message":"Worker failed to encode output","code":2,"trace":""}}';
        }

        file_put_contents($outputFile, $encoded, LOCK_EX);
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
