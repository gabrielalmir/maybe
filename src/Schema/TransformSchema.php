<?php

declare(strict_types=1);

namespace Maybe\Schema;

/**
 * @template TIn
 * @template TOut
 * @extends AbstractSchema<TOut>
 */
final class TransformSchema extends AbstractSchema
{
    /**
     * @var SchemaInterface<TIn>
     */
    private $inner;

    /**
     * @var callable(TIn):TOut
     */
    private $transform;

    /**
     * @param SchemaInterface<TIn> $inner
     * @param callable(TIn):TOut $transform
     */
    public function __construct(SchemaInterface $inner, callable $transform)
    {
        $this->inner = $inner;
        $this->transform = $transform;
    }

    /**
     * @param mixed $input
     * @return TOut
     */
    public function parse($input)
    {
        $parsed = $this->inner->parse($input);

        return ($this->transform)($parsed);
    }
}
