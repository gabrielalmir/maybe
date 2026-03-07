<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maybe\Async\Async;

$allStart = microtime(true);
$allResult = Async::all([
    'users' => Async::run(static function (): string {
        usleep(120000);

        return 'users:ok';
    }),
    'orders' => Async::run(static function (): string {
        usleep(220000);

        return 'orders:ok';
    }),
    'stock' => Async::run(static function (): string {
        usleep(150000);

        return 'stock:ok';
    }),
]);
$allElapsed = microtime(true) - $allStart;

echo '[all] ' . json_encode($allResult) . PHP_EOL;
echo '[all] elapsed=' . number_format($allElapsed, 3) . 's' . PHP_EOL;

$raceStart = microtime(true);
$winner = Async::race([
    'cache' => Async::run(static function (): string {
        usleep(60000);

        return 'cache';
    }),
    'db' => Async::run(static function (): string {
        usleep(180000);

        return 'db';
    }),
]);
$raceElapsed = microtime(true) - $raceStart;

echo '[race] winner=' . $winner . PHP_EOL;
echo '[race] elapsed=' . number_format($raceElapsed, 3) . 's' . PHP_EOL;
