<?php

declare(strict_types=1);

namespace Maybe\Schema;

use Maybe\Result\Result;

/**
 * @template T
 */
interface SchemaInterface
{
    /**
     * @param mixed $input
     * @return T
     */
    public function parse($input);

    /**
     * @param mixed $input
     * @return Result<T,ValidationErrorBag>
     */
    public function safeParse($input): Result;

    /**
     * @template U
     * @param callable(T):U $transform
     * @return SchemaInterface<U>
     */
    public function transform(callable $transform): SchemaInterface;
}

