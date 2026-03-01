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

it('parses valid dates with date schema', function (): void {
    $schema = Schema::date();

    $value = $schema->parse('2026-03-01');

    Assert::assertInstanceOf(\DateTimeImmutable::class, $value);
    Assert::assertSame('2026-03-01', $value->format('Y-m-d'));
});

it('returns err for invalid date format or bounds', function (): void {
    $schema = Schema::date()
        ->min(new \DateTimeImmutable('2024-01-01'))
        ->max(new \DateTimeImmutable('2024-12-31'));

    $invalidFormat = $schema->safeParse('2024-99-99');
    $tooEarly = $schema->safeParse('2023-12-31');
    $tooLate = $schema->safeParse('2025-01-01');

    $formatErrors = $invalidFormat->match(
        static fn ($value): ?ValidationErrorBag => null,
        static fn (ValidationErrorBag $errors): ValidationErrorBag => $errors
    );
    $earlyErrors = $tooEarly->match(
        static fn ($value): ?ValidationErrorBag => null,
        static fn (ValidationErrorBag $errors): ValidationErrorBag => $errors
    );
    $lateErrors = $tooLate->match(
        static fn ($value): ?ValidationErrorBag => null,
        static fn (ValidationErrorBag $errors): ValidationErrorBag => $errors
    );

    Assert::assertInstanceOf(ValidationErrorBag::class, $formatErrors);
    Assert::assertInstanceOf(ValidationErrorBag::class, $earlyErrors);
    Assert::assertInstanceOf(ValidationErrorBag::class, $lateErrors);
    Assert::assertSame('date.format', $formatErrors->first()->code());
    Assert::assertSame('date.min', $earlyErrors->first()->code());
    Assert::assertSame('date.max', $lateErrors->first()->code());
});
