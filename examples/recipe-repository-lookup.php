<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maybe\Option\Option;
use Maybe\Result\Result;

final class CustomerRepository
{
    /** @var array<int,array{id:int,name:string,active:bool}> */
    private $rows;

    /** @param array<int,array{id:int,name:string,active:bool}> $rows */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    /** @return Option<array{id:int,name:string,active:bool}> */
    public function findById(int $id): Option
    {
        foreach ($this->rows as $row) {
            if ($row['id'] === $id) {
                return Option::some($row);
            }
        }

        return Option::none();
    }
}

final class CustomerService
{
    /** @var CustomerRepository */
    private $repository;

    public function __construct(CustomerRepository $repository)
    {
        $this->repository = $repository;
    }

    public function activeCustomerName(int $id): Result
    {
        return $this->repository
            ->findById($id)
            ->okOr('customer_not_found')
            ->andThen(static function (array $customer): Result {
                return $customer['active']
                    ? Result::ok($customer['name'])
                    : Result::err('customer_inactive');
            });
    }
}

$repository = new CustomerRepository([
    ['id' => 1, 'name' => 'Ana Souza', 'active' => true],
    ['id' => 2, 'name' => 'Bruno Lima', 'active' => false],
]);

$service = new CustomerService($repository);

foreach ([1, 2, 999] as $id) {
    echo $service->activeCustomerName($id)->match(
        static fn (string $name): string => sprintf('customer #%d: %s', $id, $name),
        static fn (string $error): string => sprintf('customer #%d: failed (%s)', $id, $error)
    ) . "\n";
}
