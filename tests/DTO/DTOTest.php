<?php

declare(strict_types=1);

use Maybe\DTO\DTO;
use Maybe\Schema\ObjectSchema;
use Maybe\Schema\Schema;
use Maybe\Schema\ValidationErrorBag;
use PHPUnit\Framework\Assert;

it('creates dto from valid input', function (): void {
    $result = UserDTO::fromArray([
        'name' => 'Ana',
        'age' => 24,
    ]);

    $user = $result->match(
        static fn (UserDTO $dto): ?UserDTO => $dto,
        static fn (ValidationErrorBag $errors): ?UserDTO => null
    );

    Assert::assertInstanceOf(UserDTO::class, $user);
    Assert::assertSame('Ana', $user->name);
});

it('returns validation errors for invalid dto payload', function (): void {
    $result = UserDTO::fromArray([
        'name' => '',
        'age' => 10,
    ]);

    $errors = $result->match(
        static fn (UserDTO $dto): ?ValidationErrorBag => null,
        static fn (ValidationErrorBag $e): ValidationErrorBag => $e
    );

    Assert::assertInstanceOf(ValidationErrorBag::class, $errors);
    Assert::assertSame(2, $errors->count());
});

final class UserDTO extends DTO
{
    /** @var string */
    public $name;

    /** @var int */
    public $age;

    private function __construct(string $name, int $age)
    {
        $this->name = $name;
        $this->age = $age;
    }

    public static function schema(): ObjectSchema
    {
        return Schema::shape([
            'name' => Schema::string()->trimmed()->min(1),
            'age' => Schema::int()->min(18),
        ]);
    }

    protected static function fromValidated(array $validated)
    {
        return new self($validated['name'], $validated['age']);
    }
}
