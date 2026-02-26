<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maybe\Option\Option;
use Maybe\Result\Result;

$name = Option::fromNullable('  gabriel  ')
    ->map('trim')
    ->map('strtoupper')
    ->unwrapOr('GUEST');

echo "Name: {$name}\n";

$division = static function (int $a, int $b): Result {
    if ($b === 0) {
        return Result::err('division_by_zero');
    }

    return Result::ok($a / $b);
};

echo $division(10, 2)->match(
    static fn (float $value): string => "OK: {$value}",
    static fn (string $error): string => "ERR: {$error}"
) . "\n";
