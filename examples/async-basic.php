<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maybe\Async\Async;

use function Maybe\async;
use function Maybe\await;

$start = microtime(true);

$future = Async::run(static function (): int {
  usleep(200000);

  return 21 * 2;
});

$result = $future->resolve();
$elapsed = microtime(true) - $start;

echo 'result=' . $result . PHP_EOL;
echo 'elapsed=' . number_format($elapsed, 3) . 's' . PHP_EOL;

$heavy_calc = async(static function () {
  for ($i = 0; $i < 1_000; $i++) {
    $i++;
    usleep(100);
  }

  return $i;
});

$result = await($heavy_calc);
echo 'heavy calc=' . $result * 2 . PHP_EOL;
