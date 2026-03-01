<?php

declare(strict_types=1);

namespace Maybe\Schema;

final class Schema
{
    /**
     * @return StringSchema
     */
    public static function string(): StringSchema
    {
        return new StringSchema();
    }

    /**
     * @return IntSchema
     */
    public static function int(): IntSchema
    {
        return new IntSchema();
    }

    /**
     * @return BoolSchema
     */
    public static function bool(): BoolSchema
    {
        return new BoolSchema();
    }

    /**
     * @return DateSchema
     */
    public static function date(): DateSchema
    {
        return new DateSchema();
    }

    /**
     * @template T
     * @param SchemaInterface<T> $itemSchema
     * @return ArraySchema<T>
     */
    public static function arrayOf(SchemaInterface $itemSchema): ArraySchema
    {
        return new ArraySchema($itemSchema);
    }

    /**
     * @param array<string,SchemaInterface<mixed>> $shape
     * @return ObjectSchema
     */
    public static function shape(array $shape): ObjectSchema
    {
        return new ObjectSchema($shape);
    }

    /**
     * @template T
     * @param SchemaInterface<T> $inner
     * @return OptionSchema<T>
     */
    public static function option(SchemaInterface $inner): OptionSchema
    {
        return new OptionSchema($inner);
    }
}
