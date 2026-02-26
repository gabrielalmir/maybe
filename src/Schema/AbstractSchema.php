<?php

declare(strict_types=1);

namespace Maybe\Schema;

use Maybe\Result\Result;

/**
 * @template T
 * @implements SchemaInterface<T>
 */
abstract class AbstractSchema implements SchemaInterface
{
    /**
     * @param mixed $input
     * @return Result<T,ValidationErrorBag>
     */
    final public function safeParse($input): Result
    {
        try {
            return Result::ok($this->parse($input));
        } catch (ValidationException $e) {
            return Result::err($e->errors());
        }
    }

    /**
     * @template U
     * @param callable(T):U $transform
     * @return SchemaInterface<U>
     */
    final public function transform(callable $transform): SchemaInterface
    {
        return new TransformSchema($this, $transform);
    }
}

