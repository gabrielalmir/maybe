<?php

declare(strict_types=1);

namespace Maybe\DTO;

use Maybe\Result\Result;
use Maybe\Schema\ObjectSchema;
use Maybe\Schema\ValidationErrorBag;

abstract class DTO
{
    abstract public static function schema(): ObjectSchema;

    /**
     * @param array<string,mixed> $validated
     * @return static
     */
    abstract protected static function fromValidated(array $validated);

    /**
     * @param array<string,mixed> $input
     * @return Result<static,ValidationErrorBag>
     */
    final public static function fromArray(array $input): Result
    {
        return static::schema()
            ->safeParse($input)
            ->map(
                /**
                 * @param array<string,mixed> $validated
                 * @return static
                 */
                function (array $validated) {
                    return static::fromValidated($validated);
                }
            );
    }

    /**
     * @param array<string,mixed> $input
     * @return static
     */
    final public static function parse(array $input)
    {
        /** @var array<string,mixed> $validated */
        $validated = static::schema()->parse($input);

        return static::fromValidated($validated);
    }
}

