<?php

declare(strict_types=1);

namespace Maybe\Result;

use Maybe\Exception\UnwrapErrException;
use Maybe\Option\Option;

/**
 * @template T
 * @template E
 */
abstract class Result
{
    /**
     * @template U
     * @param callable(T): U $fn
     * @return Result<U,E>
     */
    abstract public function map(callable $fn): Result;

    /**
     * @template F
     * @param callable(E): F $fn
     * @return Result<T,F>
     */
    abstract public function mapErr(callable $fn): Result;

    /**
     * Chain a fallible operation on the success value (a.k.a. flatMap/bind).
     * Short-circuits on Err.
     *
     * @template U
     * @param callable(T): Result<U,E> $fn
     * @return Result<U,E>
     */
    abstract public function andThen(callable $fn): Result;

    /**
     * Recover from an error by producing a new Result. Passes Ok through.
     *
     * @template F
     * @param callable(E): Result<T,F> $fn
     * @return Result<T,F>
     */
    abstract public function orElse(callable $fn): Result;

    /**
     * @template R
     * @param callable(T): R $onOk
     * @param callable(E): R $onErr
     * @return R
     */
    abstract public function match(callable $onOk, callable $onErr);

    /**
     * @return T
     */
    abstract public function unwrap();

    /**
     * @return E
     */
    abstract public function unwrapErr();

    abstract public function isOk(): bool;

    final public function isErr(): bool
    {
        return !$this->isOk();
    }

    /**
     * Return the success value or a fallback if this is an Err.
     *
     * @param T $default
     * @return T
     */
    public function unwrapOr($default)
    {
        /** @var callable(T): T $onOk */
        $onOk = static function ($value) {
            return $value;
        };

        /** @var callable(E): T $onErr */
        $onErr = static function ($error) use ($default) {
            return $default;
        };

        return $this->match($onOk, $onErr);
    }

    /**
     * Return the success value or compute a fallback from the error.
     *
     * @param callable(E): T $fn
     * @return T
     */
    public function unwrapOrElse(callable $fn)
    {
        /** @var callable(T): T $onOk */
        $onOk = static function ($value) {
            return $value;
        };

        return $this->match($onOk, $fn);
    }

    /**
     * Return the success value or throw with a caller-supplied message.
     *
     * @param string $message
     * @return T
     */
    public function expect(string $message)
    {
        /** @var callable(T): T $onOk */
        $onOk = static function ($value) {
            return $value;
        };

        /** @var callable(E): T $onErr */
        $onErr = static function ($error) use ($message) {
            throw new UnwrapErrException($message);
        };

        return $this->match($onOk, $onErr);
    }

    /**
     * Convert to an Option, discarding the error: Ok(v) -> Some(v), Err(_) -> None.
     *
     * @return Option<T>
     */
    public function okOption(): Option
    {
        /** @var callable(T): Option<T> $onOk */
        $onOk = static function ($value): Option {
            return Option::fromNullable($value);
        };

        /** @var callable(E): Option<T> $onErr */
        $onErr = static function ($error): Option {
            return Option::none();
        };

        return $this->match($onOk, $onErr);
    }

    /**
     * Convert to an Option over the error: Err(e) -> Some(e), Ok(_) -> None.
     *
     * @return Option<E>
     */
    public function errOption(): Option
    {
        /** @var callable(T): Option<E> $onOk */
        $onOk = static function ($value): Option {
            return Option::none();
        };

        /** @var callable(E): Option<E> $onErr */
        $onErr = static function ($error): Option {
            return Option::fromNullable($error);
        };

        return $this->match($onOk, $onErr);
    }

    /**
     * @template U
     * @param U $value
     * @return Result<U,mixed>
     */
    public static function ok($value): Result
    {
        return new Ok($value);
    }

    /**
     * @template F
     * @param F $error
     * @return Result<mixed,F>
     */
    public static function err($error): Result
    {
        return new Err($error);
    }
}
