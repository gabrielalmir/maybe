<?php

declare(strict_types=1);

namespace Maybe;

use Maybe\Option\Option;
use Maybe\Result\Result;
use Maybe\Schema\ObjectSchema;
use Maybe\Schema\OptionSchema;
use Maybe\Schema\Schema;
use Maybe\Schema\SchemaInterface;

/**
 * @template T
 * @param T $value
 * @return Option<T>
 */
function some($value): Option
{
    return Option::some($value);
}

/**
 * @return Option<mixed>
 */
function none(): Option
{
    return Option::none();
}

/**
 * @template T
 * @param T|null $value
 * @return Option<T>
 */
function fromNullable($value): Option
{
    return Option::fromNullable($value);
}

/**
 * @template T
 * @param T $value
 * @return Result<T,mixed>
 */
function ok($value): Result
{
    return Result::ok($value);
}

/**
 * @template E
 * @param E $error
 * @return Result<mixed,E>
 */
function err($error): Result
{
    return Result::err($error);
}

/**
 * @return \Maybe\Schema\StringSchema
 */
function stringSchema()
{
    return Schema::string();
}

/**
 * @return \Maybe\Schema\IntSchema
 */
function intSchema()
{
    return Schema::int();
}

/**
 * @return \Maybe\Schema\BoolSchema
 */
function boolSchema()
{
    return Schema::bool();
}

/**
 * @template T
 * @param SchemaInterface<T> $itemSchema
 * @return \Maybe\Schema\ArraySchema<T>
 */
function arraySchema(SchemaInterface $itemSchema)
{
    return Schema::arrayOf($itemSchema);
}

/**
 * @param array<string,SchemaInterface<mixed>> $shape
 * @return ObjectSchema
 */
function objectSchema(array $shape): ObjectSchema
{
    return Schema::shape($shape);
}

/**
 * @template T
 * @param SchemaInterface<T> $inner
 * @return OptionSchema<T>
 */
function optionSchema(SchemaInterface $inner): OptionSchema
{
    return Schema::option($inner);
}
