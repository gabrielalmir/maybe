<?php

declare(strict_types=1);

namespace Maybe\Schema;

use Maybe\Option\Option;

/**
 * @template T
 * @extends AbstractSchema<Option<T>>
 */
final class OptionSchema extends AbstractSchema
{
    /**
     * @var SchemaInterface<T>
     */
    private $inner;

    /**
     * @param SchemaInterface<T> $inner
     */
    public function __construct(SchemaInterface $inner)
    {
        $this->inner = $inner;
    }

    /**
     * @param mixed $input
     * @return Option<T>
     */
    public function parse($input): Option
    {
        if ($input === null) {
            return Option::none();
        }

        return Option::some($this->inner->parse($input));
    }
}
