<?php

declare(strict_types=1);

namespace Maybe\Option;

/**
 * @template T
 * @extends Option<T>
 */
final class Some extends Option
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
        if ($value === null) {
            throw new \InvalidArgumentException('Some cannot contain null. Use Option::fromNullable().');
        }

        $this->value = $value;
    }

    /**
     * @template U
     * @param callable(T): U $fn
     * @return Option<U>
     */
    public function map(callable $fn): Option
    {
        // Mirror Option::fromNullable semantics: a callback returning null
        // collapses to None instead of throwing (Some cannot hold null).
        return Option::fromNullable($fn($this->value));
    }

    /**
     * @param callable(T): bool $predicate
     * @return Option<T>
     */
    public function filter(callable $predicate): Option
    {
        return $predicate($this->value) ? $this : Option::none();
    }

    /**
     * @template U
     * @param callable(T): Option<U> $fn
     * @return Option<U>
     */
    public function flatMap(callable $fn): Option
    {
        $result = $fn($this->value);

        if (!$result instanceof Option) {
            throw new \UnexpectedValueException('Option::flatMap callback must return an Option instance.');
        }

        return $result;
    }

    /**
     * @template R
     * @param callable(T): R $onSome
     * @param callable(): R $onNone
     * @return R
     */
    public function match(callable $onSome, callable $onNone)
    {
        return $onSome($this->value);
    }

    /**
     * @return T
     */
    public function unwrap()
    {
        return $this->value;
    }

    /**
     * @param T $default
     * @return T
     */
    public function unwrapOr($default)
    {
        return $this->value;
    }

    public function isSome(): bool
    {
        return true;
    }
}
