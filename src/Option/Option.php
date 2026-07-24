<?php

declare(strict_types=1);

namespace Maybe\Option;

use Maybe\Exception\UnwrapNoneException;
use Maybe\Result\Result;

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
     * Keep the value only if the predicate holds; otherwise collapse to None.
     *
     * @param callable(T): bool $predicate
     * @return Option<T>
     */
    abstract public function filter(callable $predicate): Option;

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
     * Return the value or compute a fallback lazily.
     *
     * @param callable(): T $fn
     * @return T
     */
    public function unwrapOrElse(callable $fn)
    {
        /** @var callable(T): T $onSome */
        $onSome = static function ($value) {
            return $value;
        };

        return $this->match($onSome, $fn);
    }

    /**
     * Return the value or throw with a caller-supplied message.
     *
     * @param string $message
     * @return T
     */
    public function expect(string $message)
    {
        /** @var callable(T): T $onSome */
        $onSome = static function ($value) {
            return $value;
        };

        $onNone = static function () use ($message) {
            throw new UnwrapNoneException($message);
        };

        return $this->match($onSome, $onNone);
    }

    /**
     * Convert to a Result, using $error for the None case.
     *
     * @template E
     * @param E $error
     * @return Result<T,E>
     */
    public function okOr($error): Result
    {
        /** @var callable(T): Result<T,E> $onSome */
        $onSome = static function ($value): Result {
            return Result::ok($value);
        };

        $onNone = static function () use ($error): Result {
            return Result::err($error);
        };

        return $this->match($onSome, $onNone);
    }

    /**
     * Convert to a Result, computing the error lazily for the None case.
     *
     * @template E
     * @param callable(): E $fn
     * @return Result<T,E>
     */
    public function okOrElse(callable $fn): Result
    {
        /** @var callable(T): Result<T,E> $onSome */
        $onSome = static function ($value): Result {
            return Result::ok($value);
        };

        $onNone = static function () use ($fn): Result {
            return Result::err($fn());
        };

        return $this->match($onSome, $onNone);
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
