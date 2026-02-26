<?php

declare(strict_types=1);

namespace Maybe\Option;

use Maybe\Exception\UnwrapNoneException;

/**
 * @extends Option<mixed>
 */
final class None extends Option
{
    /**
     * @var self|null
     */
    private static $instance;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @template U
     * @param callable(mixed): U $fn
     * @return Option<U>
     */
    public function map(callable $fn): Option
    {
        return self::instance();
    }

    /**
     * @template U
     * @param callable(mixed): Option<U> $fn
     * @return Option<U>
     */
    public function flatMap(callable $fn): Option
    {
        return self::instance();
    }

    /**
     * @template R
     * @param callable(mixed): R $onSome
     * @param callable(): R $onNone
     * @return R
     */
    public function match(callable $onSome, callable $onNone)
    {
        return $onNone();
    }

    /** @return mixed */
    public function unwrap()
    {
        throw new UnwrapNoneException('Cannot unwrap None. Use unwrapOr() or match() to handle absence safely.');
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public function unwrapOr($default)
    {
        return $default;
    }

    public function isSome(): bool
    {
        return false;
    }
}
