<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'WorkerRuntime.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'Ipc.php';

/** @var array<int,string> $argv */
\Maybe\Async\WorkerRuntime::run(
    $argv[1],
    $argv[2],
    $argv[3] ?? null,
    isset($argv[4]) && $argv[4] !== '' ? (int) $argv[4] : null
);
