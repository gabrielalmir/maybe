<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maybe\DTO\DTO;
use Maybe\Schema\ObjectSchema;
use Maybe\Schema\Schema;
use Maybe\Schema\ValidationErrorBag;

final class AccountDTO extends DTO
{
    /** @var string */
    public $email;

    /** @var int */
    public $age;

    private function __construct(string $email, int $age)
    {
        $this->email = $email;
        $this->age = $age;
    }

    public static function schema(): ObjectSchema
    {
        return Schema::shape([
            'email' => Schema::string()->trimmed()->min(5),
            'age' => Schema::int()->min(18),
        ]);
    }

    protected static function fromValidated(array $validated)
    {
        return new self($validated['email'], $validated['age']);
    }
}

$result = AccountDTO::fromArray([
    'email' => '  user@example.com  ',
    'age' => 23,
]);

echo $result->match(
    static function (AccountDTO $dto): string {
        return 'OK: ' . $dto->email . ' (' . $dto->age . ')';
    },
    static function (ValidationErrorBag $errors): string {
        return 'ERR: ' . $errors->summary();
    }
) . "\n";
