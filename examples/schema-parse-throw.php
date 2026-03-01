<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maybe\Schema\Schema;
use Maybe\Schema\ValidationException;

$schema = Schema::shape([
    'employee_id' => Schema::string()->trimmed()->regex('/^EMP-[0-9]{4}$/'),
    'work_email' => Schema::string()->trimmed()->regex('/^[^@\s]+@empresa\.com$/'),
    'department' => Schema::string()->trimmed()->min(3),
]);

try {
    $data = $schema->parse([
        'employee_id' => '1020',
        'work_email' => 'julia@gmail.com',
        'department' => 'TI',
    ]);

    echo 'Colaborador importado: ' . $data['employee_id'] . "\n";
} catch (ValidationException $e) {
    echo "Falha ao importar colaborador:\n";

    foreach ($e->errors()->all() as $error) {
        echo sprintf("- %s (%s): %s\n", $error->path(), $error->code(), $error->message());
    }
}
