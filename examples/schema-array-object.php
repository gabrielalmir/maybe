<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maybe\Schema\Schema;
use Maybe\Schema\ValidationErrorBag;

$orderSchema = Schema::shape([
    'order_id' => Schema::string()->trimmed()->regex('/^ORD-[0-9]{6}$/'),
    'customer' => Schema::shape([
        'name' => Schema::string()->trimmed()->min(3),
        'email' => Schema::string()->trimmed()->regex('/^[^@\s]+@[^@\s]+\.[^@\s]+$/'),
    ]),
    'items' => Schema::arrayOf(
        Schema::shape([
            'sku' => Schema::string()->trimmed()->regex('/^[A-Z0-9_-]{4,20}$/'),
            'quantity' => Schema::int()->min(1),
            'unit_price' => Schema::int()->min(1),
        ])
    ),
    'notes' => Schema::option(Schema::string()->trimmed()->max(120)),
]);

$result = $orderSchema->safeParse([
    'order_id' => 'ORD-102938',
    'customer' => [
        'name' => '  Marina Costa  ',
        'email' => 'marina@lojax.com',
    ],
    'items' => [
        ['sku' => 'KB_001', 'quantity' => 2, 'unit_price' => 25000],
        ['sku' => 'X', 'quantity' => 0, 'unit_price' => 12000],
    ],
    'notes' => '  Entregar apenas em horario comercial  ',
]);

echo $result->match(
    static function (array $order): string {
        $total = 0;

        foreach ($order['items'] as $item) {
            $total += $item['quantity'] * $item['unit_price'];
        }

        return sprintf(
            'OK: pedido %s para %s | itens: %d | total: %d',
            $order['order_id'],
            $order['customer']['name'],
            count($order['items']),
            $total
        );
    },
    static function (ValidationErrorBag $errors): string {
        $lines = array_map(
            static fn ($error): string => sprintf('%s => %s', $error->path(), $error->message()),
            $errors->all()
        );

        return "Pedido invalido:\n- " . implode("\n- ", $lines);
    }
) . "\n";
