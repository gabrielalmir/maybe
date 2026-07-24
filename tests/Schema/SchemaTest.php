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
    Assert::assertSame('date.format', $formatErrors->toArray()[0]['code']);
    Assert::assertSame('date.min', $earlyErrors->toArray()[0]['code']);
    Assert::assertSame('date.max', $lateErrors->toArray()[0]['code']);
});


it('accepts dates exactly on min and max boundaries', function (): void {
    $schema = Schema::date()
        ->min(new \DateTimeImmutable('2024-01-01 12:34:56'))
        ->max(new \DateTimeImmutable('2024-12-31 12:34:56'));

    Assert::assertSame('2024-01-01', $schema->parse('2024-01-01')->format('Y-m-d'));
    Assert::assertSame('2024-12-31', $schema->parse('2024-12-31')->format('Y-m-d'));
});

it('parses valid values with enumeration schema', function (): void {
    $schema = Schema::enumeration(['pending', 'paid', 'failed']);

    Assert::assertSame('paid', $schema->parse('paid'));
});

it('returns err for invalid enumeration value', function (): void {
    $schema = Schema::enumeration(['pending', 'paid', 'failed']);
    $result = $schema->safeParse('processing');

    $errors = $result->match(
        static fn ($value): ?ValidationErrorBag => null,
        static fn (ValidationErrorBag $errors): ValidationErrorBag => $errors
    );

    Assert::assertInstanceOf(ValidationErrorBag::class, $errors);
    Assert::assertSame('enum.invalid', $errors->toArray()[0]['code']);
});

it('throws when enumeration schema is created with empty values', function (): void {
    $this->expectException(\InvalidArgumentException::class);

    Schema::enumeration([]);
});

it('exposes errors through iteration, describe and toArray', function (): void {
    $schema = Schema::shape([
        'name' => Schema::string()->trimmed()->min(1),
        'age' => Schema::int()->min(18),
    ]);

    $errors = $schema->safeParse(['name' => ' ', 'age' => 15])->match(
        static fn (array $v): ?ValidationErrorBag => null,
        static fn (ValidationErrorBag $e): ValidationErrorBag => $e
    );

    Assert::assertCount(2, $errors);

    $iterated = [];
    foreach ($errors as $error) {
        $iterated[] = $error->describedAs();
    }

    Assert::assertSame($iterated, $errors->describe());

    $rows = $errors->toArray();
    Assert::assertSame('$.name', $rows[0]['path']);
    Assert::assertArrayHasKey('code', $rows[0]);
    Assert::assertStringContainsString(':', $errors->describe()[0]);
});

it('reports nested array paths through the value objects', function (): void {
    $schema = Schema::arrayOf(Schema::shape([
        'email' => Schema::string()->min(5),
    ]));

    $errors = $schema->safeParse([['email' => 'ana@x.io'], ['email' => 'no']])->match(
        static fn (array $v): ?ValidationErrorBag => null,
        static fn (ValidationErrorBag $e): ValidationErrorBag => $e
    );

    Assert::assertSame('$[1].email', $errors->toArray()[0]['path']);
});
