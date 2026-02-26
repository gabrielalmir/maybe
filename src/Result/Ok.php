<?php

declare(strict_types=1);

namespace Maybe\Result;

use Maybe\Exception\UnwrapOkException;

/**
 * @template T
 * @template E
 * @extends Result<T,E>
 */
final class Ok extends Result
{
    /**
     * @var T
     */
    private $value;

    /**
     * @param T $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @template U
     * @param callable(T): U $fn
     * @return Result<U,E>
     */
    public function map(callable $fn): Result
    {
        return new self($fn($this->value));
    }

    /**
     * @template F
     * @param callable(E): F $fn
     * @return Result<T,F>
     */
    public function mapErr(callable $fn): Result
    {
        /** @var Result<T,F> $result */
        $result = $this;

        return $result;
    }

    /**
     * @template R
     * @param callable(T): R $onOk
     * @param callable(E): R $onErr
     * @return R
     */
    public function match(callable $onOk, callable $onErr)
    {
        return $onOk($this->value);
    }

    /**
     * @return T
     */
    public function unwrap()
    {
        return $this->value;
    }

    /**
     * @return E
     */
    public function unwrapErr()
    {
        throw new UnwrapOkException('Cannot unwrapErr() on Ok. Use match() or check isErr() first.');
    }

    public function isOk(): bool
    {
        return true;
    }
}
