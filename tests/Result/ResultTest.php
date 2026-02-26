<?php

declare(strict_types=1);

use Maybe\Exception\UnwrapErrException;
use Maybe\Exception\UnwrapOkException;
use Maybe\Result\Result;
use PHPUnit\Framework\Assert;

it('maps ok values', function (): void {
    $value = Result::ok(2)->map(static fn (int $n): int => $n + 3)->unwrap();

    Assert::assertSame(5, $value);
});

it('maps err values with mapErr', function (): void {
    $error = Result::err('bad')->mapErr('strtoupper')->unwrapErr();

    Assert::assertSame('BAD', $error);
});

it('throws when unwrapping err', function (): void {
    Result::err('x')->unwrap();
})->throws(UnwrapErrException::class);

it('throws when unwrapping error from ok', function (): void {
    Result::ok('x')->unwrapErr();
})->throws(UnwrapOkException::class);
