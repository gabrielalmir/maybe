<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maybe\Async\Async;

$tasks = [];
for ($i = 1; $i <= 10; $i++) {
  $tasks[] = [
    static function (int $n): array {
      usleep(120000);

      return [
        'item' => $n,
        'square' => $n * $n,
      ];
    },
    [$i],
  ];
}

$start = microtime(true);
$results = Async::pool($tasks, 3);
$elapsed = microtime(true) - $start;

echo 'processed=' . count($results) . PHP_EOL;
echo 'first=' . json_encode($results[0]) . PHP_EOL;
echo 'last=' . json_encode($results[9]) . PHP_EOL;
echo 'elapsed=' . number_format($elapsed, 3) . 's' . PHP_EOL;
