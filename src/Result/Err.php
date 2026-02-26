<?php

declare(strict_types=1);

namespace Maybe\Result;

use Maybe\Exception\UnwrapErrException;

/**
 * @template T
 * @template E
 * @extends Result<T,E>
 */
final class Err extends Result
{
    /**
     * @var E
     */
    private $error;

    /**
     * @param E $error
     */
    public function __construct($error)
    {
        $this->error = $error;
    }

    /**
     * @template U
     * @param callable(T): U $fn
     * @return Result<U,E>
     */
    public function map(callable $fn): Result
    {
        /** @var Result<U,E> $result */
        $result = $this;

        return $result;
    }

    /**
     * @template F
     * @param callable(E): F $fn
     * @return Result<T,F>
     */
    public function mapErr(callable $fn): Result
    {
        return new self($fn($this->error));
    }

    /**
     * @template R
     * @param callable(T): R $onOk
     * @param callable(E): R $onErr
     * @return R
     */
    public function match(callable $onOk, callable $onErr)
    {
        return $onErr($this->error);
    }

    /**
     * @return T
     */
    public function unwrap()
    {
        $message = sprintf(
            'Cannot unwrap() on Err. Error payload type: %s. Use match() or unwrapErr() instead.',
            is_object($this->error) ? get_class($this->error) : gettype($this->error)
        );

        throw new UnwrapErrException($message);
    }

    /**
     * @return E
     */
    public function unwrapErr()
    {
        return $this->error;
    }

    public function isOk(): bool
    {
        return false;
    }
}
