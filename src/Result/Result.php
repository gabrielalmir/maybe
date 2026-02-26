<?php

declare(strict_types=1);

namespace Maybe\Result;

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
