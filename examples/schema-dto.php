<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maybe\DTO\DTO;
use Maybe\Option\Option;
use Maybe\Schema\ObjectSchema;
use Maybe\Schema\Schema;
use Maybe\Schema\ValidationErrorBag;

final class CustomerRegistrationDTO extends DTO
{
    /** @var string */
    public $fullName;

    /** @var string */
    public $email;

    /** @var int */
    public $birthYear;

    /** @var Option<string> */
    public $phoneNumber;

    /** @var bool */
    public $acceptsMarketing;

    private function __construct(
        string $fullName,
        string $email,
        int $birthYear,
        Option $phoneNumber,
        bool $acceptsMarketing
    )
    {
        $this->fullName = $fullName;
        $this->email = $email;
        $this->birthYear = $birthYear;
        $this->phoneNumber = $phoneNumber;
        $this->acceptsMarketing = $acceptsMarketing;
    }

    public static function schema(): ObjectSchema
    {
        return Schema::shape([
            'full_name' => Schema::string()->trimmed()->min(5),
            'email' => Schema::string()->trimmed()->regex('/^[^@\s]+@[^@\s]+\.[^@\s]+$/'),
            'birth_year' => Schema::int()->min(1900)->max(2010),
            'phone_number' => Schema::option(
                Schema::string()->trimmed()->regex('/^\+?[0-9]{10,15}$/')
            ),
            'accepts_marketing' => Schema::bool(),
        ]);
    }

    protected static function fromValidated(array $validated)
    {
        return new self(
            $validated['full_name'],
            $validated['email'],
            $validated['birth_year'],
            $validated['phone_number'],
            $validated['accepts_marketing']
        );
    }
}

foreach (
    [
        [
            'full_name' => '  Ana Souza  ',
            'email' => 'ana.souza@empresa.com',
            'birth_year' => 1994,
            'phone_number' => '+5511999999999',
            'accepts_marketing' => true,
        ],
        [
            'full_name' => 'Lu',
            'email' => 'email-invalido',
            'birth_year' => 1880,
            'phone_number' => 'abc',
            'accepts_marketing' => 'yes',
        ],
    ] as $payload
) {
    $result = CustomerRegistrationDTO::fromArray($payload);

    echo $result->match(
        static function (CustomerRegistrationDTO $dto): string {
            return sprintf(
                'OK: %s <%s> | nascimento: %d | telefone: %s | marketing: %s',
                $dto->fullName,
                $dto->email,
                $dto->birthYear,
                $dto->phoneNumber->unwrapOr('nao informado'),
                $dto->acceptsMarketing ? 'sim' : 'nao'
            );
        },
        static function (ValidationErrorBag $errors): string {
            $messages = array_map(
                static fn ($error): string => sprintf('%s => %s', $error->path(), $error->message()),
                $errors->all()
            );

            return "Cadastro invalido:\n- " . implode("\n- ", $messages);
        }
    ) . "\n\n";
}
