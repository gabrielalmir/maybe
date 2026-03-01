<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maybe\Option\Option;
use Maybe\Result\Result;

$customerName = Option::fromNullable('  Ana Souza  ')
    ->map('trim')
    ->flatMap(static function (string $name): Option {
        return $name === '' ? Option::none() : Option::some($name);
    })
    ->unwrapOr('Cliente');

$applyCoupon = static function (int $subtotalInCents, Option $couponCode): Result {
    return $couponCode->match(
        static function (string $code) use ($subtotalInCents): Result {
            $normalized = strtoupper(trim($code));

            if ($normalized === 'BEMVINDO10') {
                return Result::ok($subtotalInCents - (int) round($subtotalInCents * 0.10));
            }

            if ($normalized === 'FRETEGRATIS') {
                return Result::ok($subtotalInCents);
            }

            return Result::err('Cupom inválido ou expirado');
        },
        static fn (): Result => Result::ok($subtotalInCents)
    );
};

$authorizePayment = static function (int $amountInCents, string $method): Result {
    if ($method !== 'credit_card') {
        return Result::err('Forma de pagamento não suportada');
    }

    if ($amountInCents > 500000) {
        return Result::err('Pagamento recusado pelo antifraude');
    }

    return Result::ok('pay_' . substr(sha1((string) $amountInCents), 0, 8));
};

$checkout = static function (int $subtotalInCents, ?string $couponCode, string $method) use ($applyCoupon, $authorizePayment): Result {
    $coupon = Option::fromNullable($couponCode)
        ->map('trim')
        ->flatMap(static fn (string $value): Option => $value === '' ? Option::none() : Option::some($value));

    $discounted = $applyCoupon($subtotalInCents, $coupon);

    if ($discounted->isErr()) {
        return $discounted;
    }

    return $authorizePayment($discounted->unwrap(), $method);
};

foreach (
    [
        ['subtotal' => 12990, 'coupon' => 'BEMVINDO10', 'method' => 'credit_card'],
        ['subtotal' => 8900, 'coupon' => 'INVALIDO', 'method' => 'credit_card'],
        ['subtotal' => 19900, 'coupon' => null, 'method' => 'pix'],
    ] as $order
) {
    echo $checkout($order['subtotal'], $order['coupon'], $order['method'])->match(
        static fn (string $transactionId): string => sprintf('%s: pedido confirmado (%s)', $customerName, $transactionId),
        static fn (string $error): string => sprintf('%s: checkout falhou - %s', $customerName, $error)
    ) . "\n";
}
