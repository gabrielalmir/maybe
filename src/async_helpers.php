<?php

declare(strict_types=1);

use Maybe\Async\Async;
use Maybe\Async\AsyncFuture;

if (!function_exists('async')) {
    /**
     * @param array<int,mixed> $args
     * @param array<string,mixed> $options
     */
    function async(callable $task, array $args = [], array $options = []): AsyncFuture
    {
        return Async::run($task, $args, $options);
    }
}

if (!function_exists('await')) {
    /**
     * @param mixed|AsyncFuture|array<mixed,AsyncFuture|mixed> $value
     * @return mixed
     */
    function await($value)
    {
        if ($value instanceof AsyncFuture) {
            return $value->resolve();
        }

        if (is_array($value)) {
            return Async::all($value);
        }

        return $value;
    }
}
