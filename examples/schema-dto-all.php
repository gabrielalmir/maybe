<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maybe\DTO\DTO;
use Maybe\Option\Option;
use Maybe\Schema\ObjectSchema;
use Maybe\Schema\Schema;
use Maybe\Schema\ValidationErrorBag;

final class OrderImportDTO extends DTO
{
    /** @var string */
    public $orderId;

    /** @var \DateTimeImmutable */
    public $createdAt;

    /** @var int */
    public $itemCount;

    /** @var bool */
    public $paid;

    /** @var array<int,string> */
    public $tags;

    /** @var array<string,mixed> */
    public $shippingAddress;

    /** @var Option<string> */
    public $couponCode;

    /**
     * @param array<int,string> $tags
     * @param array<string,mixed> $shippingAddress
     */
    private function __construct(
        string $orderId,
        \DateTimeImmutable $createdAt,
        int $itemCount,
        bool $paid,
        array $tags,
        array $shippingAddress,
        Option $couponCode
    ) {
        $this->orderId = $orderId;
        $this->createdAt = $createdAt;
        $this->itemCount = $itemCount;
        $this->paid = $paid;
        $this->tags = $tags;
        $this->shippingAddress = $shippingAddress;
        $this->couponCode = $couponCode;
    }

    public static function schema(): ObjectSchema
    {
        return Schema::shape([
            'order_id' => Schema::string()->trimmed()->regex('/^ORD-[0-9]{6}$/'),
            'created_at' => Schema::date()
                ->min(new \DateTimeImmutable('2020-01-01'))
                ->max(new \DateTimeImmutable('2030-12-31')),
            'item_count' => Schema::int()->min(1)->max(999),
            'paid' => Schema::bool(),
            'tags' => Schema::arrayOf(Schema::string()->trimmed()->min(2)),
            'shipping_address' => Schema::shape([
                'street' => Schema::string()->trimmed()->min(5),
                'city' => Schema::string()->trimmed()->min(2),
                'zip' => Schema::string()->trimmed()->regex('/^[0-9]{5}-?[0-9]{3}$/'),
            ]),
            'coupon_code' => Schema::option(
                Schema::string()->trimmed()->regex('/^[A-Z0-9_-]{4,20}$/')
            ),
        ]);
    }

    protected static function fromValidated(array $validated)
    {
        return new self(
            $validated['order_id'],
            $validated['created_at'],
            $validated['item_count'],
            $validated['paid'],
            $validated['tags'],
            $validated['shipping_address'],
            $validated['coupon_code']
        );
    }
}

foreach (
    [
        [
            'order_id' => 'ORD-123456',
            'created_at' => '2026-02-28',
            'item_count' => 3,
            'paid' => true,
            'tags' => ['express', 'b2b'],
            'shipping_address' => [
                'street' => 'Av. Paulista, 1000',
                'city' => 'Sao Paulo',
                'zip' => '01310-100',
            ],
            'coupon_code' => null,
        ],
        [
            'order_id' => '123',
            'created_at' => '2019-05-01',
            'item_count' => 0,
            'paid' => 'yes',
            'tags' => ['x'],
            'shipping_address' => [
                'street' => 'Rua',
                'city' => '',
                'zip' => 'abc',
            ],
            'coupon_code' => '!!',
        ],
    ] as $payload
) {
    $result = OrderImportDTO::fromArray($payload);

    echo $result->match(
        static function (OrderImportDTO $dto): string {
            return sprintf(
                'OK: %s | date: %s | items: %d | paid: %s | city: %s | coupon: %s',
                $dto->orderId,
                $dto->createdAt->format('Y-m-d'),
                $dto->itemCount,
                $dto->paid ? 'yes' : 'no',
                $dto->shippingAddress['city'],
                $dto->couponCode->unwrapOr('none')
            );
        },
        static function (ValidationErrorBag $errors): string {
            $messages = array_map(
                static fn ($error): string => sprintf('%s => %s', $error->path(), $error->message()),
                $errors->all()
            );

            return "ERR:\n- " . implode("\n- ", $messages);
        }
    ) . "\n\n";
}
