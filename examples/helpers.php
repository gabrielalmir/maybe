<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use function Maybe\err;
use function Maybe\fromNullable;
use function Maybe\intSchema;
use function Maybe\objectSchema;
use function Maybe\ok;
use function Maybe\optionSchema;
use function Maybe\stringSchema;

$preferredName = fromNullable('  Paty  ')
    ->map('trim')
    ->flatMap(static function (string $name) {
        return $name === '' ? \Maybe\none() : \Maybe\some($name);
    })
    ->unwrapOr('Cliente');

echo "Nome para exibicao: {$preferredName}\n";

$calculateLoyaltyTier = static function (int $points) {
    if ($points < 0) {
        return err('pontuacao negativa');
    }

    if ($points >= 5000) {
        return ok('gold');
    }

    if ($points >= 2000) {
        return ok('silver');
    }

    return ok('bronze');
};

echo $calculateLoyaltyTier(3200)->match(
    static fn (string $tier): string => "Nivel fidelidade: {$tier}",
    static fn (string $error): string => "Falha no nivel: {$error}"
) . "\n";

echo $calculateLoyaltyTier(-5)->match(
    static fn (string $tier): string => "Nivel fidelidade: {$tier}",
    static fn (string $error): string => "Falha no nivel: {$error}"
) . "\n";

$profileUpdateSchema = objectSchema([
    'name' => stringSchema()->trimmed()->min(3),
    'birth_year' => intSchema()->min(1900)->max(2010),
    'phone' => optionSchema(stringSchema()->trimmed()->regex('/^\+?[0-9]{10,15}$/')),
]);

$result = $profileUpdateSchema->safeParse([
    'name' => '  Patricia Lima  ',
    'birth_year' => 1991,
    'phone' => null,
]);

echo $result->match(
    static fn (array $data): string => 'Perfil pronto para persistir: ' . $data['name'],
    static fn ($errors): string => 'Falha ao validar perfil: ' . $errors->summary()
) . "\n";
