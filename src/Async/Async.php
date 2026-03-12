<?php

declare(strict_types=1);

namespace Maybe\Async;

use Closure;
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

  /**
   * @param array<int,mixed> $args
   * @param array<string,mixed> $options
   */
  public static function run(callable $task, array $args = [], array $options = []): AsyncFuture
  {
    $id = self::makeId();
    $tempDir = isset($options['temp_dir']) ? (string) $options['temp_dir'] : self::tempDir();

    if (!is_dir($tempDir) && !@mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
      throw new RuntimeException('Failed to create async temp dir: ' . $tempDir);
    }

    $inputFile = $tempDir . DIRECTORY_SEPARATOR . $id . '_input.php';
    $outputFile = $tempDir . DIRECTORY_SEPARATOR . $id . '_output.json';
    $workerFile = $tempDir . DIRECTORY_SEPARATOR . $id . '_worker.php';

    $payloadData = [
      'kind' => 'callable',
      'task' => '',
      'args' => serialize($args),
    ];

    if ($task instanceof Closure) {
      self::ensureOpisClosureLoaded();
      $payloadData['kind'] = 'closure';
      $payloadData['task'] = \Opis\Closure\serialize($task);
    } else {
      $payloadData['task'] = serialize($task);
    }

    $payload = "<?php\n\nreturn " . var_export($payloadData, true) . ";\n";

    if (file_put_contents($inputFile, $payload, LOCK_EX) === false) {
      throw new RuntimeException('Failed to write async input file: ' . $inputFile);
    }

    $runtimeFile = __DIR__ . DIRECTORY_SEPARATOR . 'WorkerRuntime.php';
    $workerCode =
      "<?php\n" .
      "declare(strict_types=1);\n" .
      'require ' .
      var_export($runtimeFile, true) .
      ";\n" .
      "\\Maybe\\Async\\WorkerRuntime::run(\$argv[1], \$argv[2], \$argv[3] ?? null);\n";

    if (file_put_contents($workerFile, $workerCode, LOCK_EX) === false) {
      @unlink($inputFile);
      throw new RuntimeException('Failed to write async worker file: ' . $workerFile);
    }

    $phpBinary = isset($options['php_binary']) ? (string) $options['php_binary'] : PHP_BINARY;
    $autoload = isset($options['autoload'])
      ? (string) $options['autoload']
      : self::resolveAutoloadPath();

    $command =
      escapeshellarg($phpBinary) .
      ' ' .
      escapeshellarg($workerFile) .
      ' ' .
      escapeshellarg($inputFile) .
      ' ' .
      escapeshellarg($outputFile) .
      ' ' .
      escapeshellarg($autoload ?? '');

    $descriptors = [
      0 => ['pipe', 'r'],
      1 => ['pipe', 'w'],
      2 => ['pipe', 'w'],
    ];

    $pipes = [];
    $process = proc_open($command, $descriptors, $pipes, null, null, ['bypass_shell' => true]);

    if (!is_resource($process)) {
      @unlink($inputFile);
      @unlink($workerFile);
      throw new RuntimeException('Failed to start async process');
    }

    if (isset($pipes[0]) && is_resource($pipes[0])) {
      fclose($pipes[0]);
      unset($pipes[0]);
    }

    $timeout = array_key_exists('timeout', $options)
      ? ($options['timeout'] === null
        ? null
        : (float) $options['timeout'])
      : self::$defaultTimeoutSeconds;

    $pollInterval = isset($options['poll_interval'])
      ? (int) $options['poll_interval']
      : self::$defaultPollIntervalMicros;

    return new AsyncFuture(
      $process,
      $pipes,
      $inputFile,
      $outputFile,
      $workerFile,
      $timeout,
      $pollInterval,
    );
  }

  /**
   * @param array<mixed,AsyncFuture> $futures
   * @return array<mixed,mixed>
   */
  public static function all(array $futures): array
  {
    $results = [];

    foreach ($futures as $key => $future) {
      if (!$future instanceof AsyncFuture) {
        $results[$key] = $future;
        continue;
      }

      $results[$key] = $future->resolve();
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

    while (true) {
      foreach ($futures as $key => $future) {
        if (!$future instanceof AsyncFuture) {
          foreach ($futures as $otherKey => $otherFuture) {
            if ($otherKey !== $key && $otherFuture instanceof AsyncFuture) {
              $otherFuture->cancel();
            }
          }

          return $future;
        }

        if (!$future->pending()) {
          $winner = $future->resolve();

          foreach ($futures as $otherKey => $otherFuture) {
            if ($otherKey !== $key && $otherFuture instanceof AsyncFuture) {
              $otherFuture->cancel();
            }
          }

          return $winner;
        }
      }

      usleep(self::$defaultPollIntervalMicros);
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
    $pollInterval = isset($options['poll_interval'])
      ? (int) $options['poll_interval']
      : self::$defaultPollIntervalMicros;

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

        try {
          $results[$key] = $future->resolve();
        } catch (\Throwable $e) {
          foreach ($running as $other) {
            $other->cancel();
          }

          throw $e;
        }

        unset($running[$key]);
      }

      if ($running !== []) {
        usleep($pollInterval);
      }
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
    self::$defaultTimeoutSeconds = $seconds;
  }

  public static function setDefaultPollInterval(int $microseconds): void
  {
    self::$defaultPollIntervalMicros = $microseconds;
  }

  private static function tempDir(): string
  {
    if (self::$defaultTempDir !== null) {
      return self::$defaultTempDir;
    }

    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'maybe-async';
  }

  private static function makeId(): string
  {
    try {
      return bin2hex(random_bytes(8));
    } catch (\Throwable $e) {
      return uniqid('async_', true);
    }
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
      dirname(__DIR__, 2) .
      DIRECTORY_SEPARATOR .
      'vendor' .
      DIRECTORY_SEPARATOR .
      'opis' .
      DIRECTORY_SEPARATOR .
      'closure' .
      DIRECTORY_SEPARATOR .
      'autoload.php',
      dirname(__DIR__, 4) .
      DIRECTORY_SEPARATOR .
      'opis' .
      DIRECTORY_SEPARATOR .
      'closure' .
      DIRECTORY_SEPARATOR .
      'autoload.php',
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
      /** @var callable $callable */
      $callable = $task[0];
      /** @var array<int,mixed> $args */
      $args = isset($task[1]) && is_array($task[1]) ? $task[1] : [];
      /** @var array<string,mixed> $taskOptions */
      $taskOptions = isset($task[2]) && is_array($task[2]) ? $task[2] : $options;

      return self::run($callable, $args, $taskOptions);
    }

    throw new TaskFailedException(
      'Pool task must be callable, AsyncFuture, or [callable, args, options]',
    );
  }
}
