<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maybe\Async\Async;
use Maybe\Async\Exception\TimeoutException;

$chain = Async::run(static function (): array {
    usleep(100000);

    return ['count' => 3];
})
    ->then(static function (array $data): array {
        $data['count'] *= 2;

        return $data;
    })
    ->then(static function (array $data): string {
        return 'count=' . $data['count'];
    })
    ->catch(static function (Throwable $e): string {
        return 'error=' . $e->getMessage();
    })
    ->finally(static function (): void {
        echo "finally: chain finished" . PHP_EOL;
    });

echo $chain->resolve() . PHP_EOL;

$slow = Async::run(static function (): string {
    usleep(300000);

    return 'too late';
}, [], ['timeout' => 0.05]);

try {
    $slow->resolve();
} catch (TimeoutException $e) {
    echo 'timeout=' . $e->getMessage() . PHP_EOL;
}

$cancellable = Async::run(static function (): string {
    usleep(300000);

    return 'done';
});

usleep(30000);
$cancellable->cancel();

echo 'cancelled pending=' . ($cancellable->pending() ? 'yes' : 'no') . PHP_EOL;
