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

it('chains fallible operations with andThen', function (): void {
    $value = Result::ok(2)
        ->andThen(static fn (int $n): Result => Result::ok($n * 10))
        ->unwrap();

    Assert::assertSame(20, $value);
});

it('short-circuits andThen on err', function (): void {
    $result = Result::err('boom')
        ->andThen(static fn ($n): Result => Result::ok('never'));

    Assert::assertSame('boom', $result->unwrapErr());
});

it('rejects non-Result return from andThen', function (): void {
    Result::ok(1)->andThen(static fn ($n): int => $n);
})->throws(UnexpectedValueException::class);

it('recovers from err with orElse', function (): void {
    $value = Result::err('boom')
        ->orElse(static fn (string $e): Result => Result::ok('recovered'))
        ->unwrap();

    Assert::assertSame('recovered', $value);
});

it('passes ok through orElse', function (): void {
    $value = Result::ok('kept')
        ->orElse(static fn ($e): Result => Result::ok('other'))
        ->unwrap();

    Assert::assertSame('kept', $value);
});

it('unwrapOr returns default on err', function (): void {
    Assert::assertSame('fallback', Result::err('x')->unwrapOr('fallback'));
    Assert::assertSame('kept', Result::ok('kept')->unwrapOr('fallback'));
});

it('unwrapOrElse computes fallback from error', function (): void {
    $value = Result::err('boom')->unwrapOrElse(static fn (string $e): string => strtoupper($e));

    Assert::assertSame('BOOM', $value);
});

it('expect throws with custom message on err', function (): void {
    Result::err('x')->expect('needed a value');
})->throws(UnwrapErrException::class, 'needed a value');

it('converts to option with okOption and errOption', function (): void {
    Assert::assertTrue(Result::ok(1)->okOption()->isSome());
    Assert::assertTrue(Result::err('e')->okOption()->isNone());
    Assert::assertTrue(Result::err('e')->errOption()->isSome());
    Assert::assertTrue(Result::ok(1)->errOption()->isNone());
});
