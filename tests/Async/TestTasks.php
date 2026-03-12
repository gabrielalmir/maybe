<?php

declare(strict_types=1);

namespace Maybe\Tests\Async;

use RuntimeException;

final class TestTasks
{
  public static function sleepAndReturnInt(int $microseconds, int $value): int
  {
    usleep($microseconds);

    return $value;
  }

  public static function sleepAndReturnString(int $microseconds, string $value): string
  {
    usleep($microseconds);

    return $value;
  }

  public static function returnFive(): int
  {
    return 5;
  }

  public static function throwBoom(): void
  {
    throw new RuntimeException('boom');
  }
}
