<?php

declare(strict_types=1);

namespace Maybe\Option;

/**
 * @template T
 */
abstract class Option
{
    /**
     * @template U
     * @param callable(T): U $fn
     * @return Option<U>
     */
    abstract public function map(callable $fn): Option;

    /**
     * @template U
     * @param callable(T): Option<U> $fn
     * @return Option<U>
     */
    abstract public function flatMap(callable $fn): Option;

    /**
     * @template R
     * @param callable(T): R $onSome
     * @param callable(): R $onNone
     * @return R
     */
    abstract public function match(callable $onSome, callable $onNone);

    /**
     * @return T
     */
    abstract public function unwrap();

    /**
     * @param T $default
     * @return T
     */
    abstract public function unwrapOr($default);

    abstract public function isSome(): bool;

    final public function isNone(): bool
    {
        return !$this->isSome();
    }

    /**
     * @template U
     * @param U $value
     * @return Option<U>
     */
    public static function some($value): Option
    {
        return new Some($value);
    }

    /**
     * @return Option<mixed>
     */
    public static function none(): Option
    {
        /** @var Option<mixed> $none */
        $none = None::instance();

        return $none;
    }

    /**
     * @template U
     * @param U|null $value
     * @return Option<U>
     */
    public static function fromNullable($value): Option
    {
        return $value === null ? self::none() : self::some($value);
    }
}
