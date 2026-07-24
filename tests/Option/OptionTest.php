<?php

declare(strict_types=1);

use Maybe\Exception\UnwrapNoneException;
use Maybe\Option\Option;
use PHPUnit\Framework\Assert;

it('creates none from nullable null', function (): void {
    Assert::assertTrue(Option::fromNullable(null)->isNone());
});

it('maps some values', function (): void {
    $value = Option::some('ana')->map('strtoupper')->unwrap();

    Assert::assertSame('ANA', $value);
});

it('flatMap can return none', function (): void {
    $option = Option::some('')->flatMap(static function (string $value): Option {
        return $value === '' ? Option::none() : Option::some($value);
    });

    Assert::assertTrue($option->isNone());
});

it('throws when unwrapping none', function (): void {
    Option::none()->unwrap();
})->throws(UnwrapNoneException::class);

it('collapses to none when map callback returns null', function (): void {
    $option = Option::some('x')->map(static fn (string $v) => null);

    Assert::assertTrue($option->isNone());
});

it('filters some values by predicate', function (): void {
    Assert::assertTrue(Option::some(4)->filter(static fn (int $n): bool => $n > 10)->isNone());
    Assert::assertTrue(Option::some(4)->filter(static fn (int $n): bool => $n > 1)->isSome());
    Assert::assertTrue(Option::none()->filter(static fn ($n): bool => true)->isNone());
});

it('unwrapOrElse computes fallback lazily', function (): void {
    Assert::assertSame('fallback', Option::none()->unwrapOrElse(static fn (): string => 'fallback'));
    Assert::assertSame('kept', Option::some('kept')->unwrapOrElse(static fn (): string => 'fallback'));
});

it('expect throws with custom message on none', function (): void {
    Option::none()->expect('missing name');
})->throws(UnwrapNoneException::class, 'missing name');

it('converts to result with okOr', function (): void {
    Assert::assertSame('v', Option::some('v')->okOr('e')->unwrap());
    Assert::assertSame('e', Option::none()->okOr('e')->unwrapErr());
});

it('converts to result with okOrElse', function (): void {
    Assert::assertSame('computed', Option::none()->okOrElse(static fn (): string => 'computed')->unwrapErr());
});
