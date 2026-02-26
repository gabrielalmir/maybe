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
