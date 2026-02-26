<?php

declare(strict_types=1);

use Maybe\Schema\Schema;
use Maybe\Schema\ValidationErrorBag;
use PHPUnit\Framework\Assert;

it('returns err on invalid shape input', function (): void {
    $schema = Schema::shape([
        'name' => Schema::string()->trimmed()->min(1),
        'age' => Schema::int()->min(18),
    ]);

    $result = $schema->safeParse([
        'name' => ' ',
        'age' => 15,
    ]);

    $errors = $result->match(
        static fn (array $v): ?ValidationErrorBag => null,
        static fn (ValidationErrorBag $e): ValidationErrorBag => $e
    );

    Assert::assertInstanceOf(ValidationErrorBag::class, $errors);
    Assert::assertSame(2, $errors->count());
});

it('parses valid arrays with arrayOf', function (): void {
    $schema = Schema::arrayOf(Schema::int()->min(1));

    Assert::assertSame([1, 2, 3], $schema->parse([1, 2, 3]));
});
