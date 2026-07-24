<?php

declare(strict_types=1);

namespace Maybe\Schema;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * A first-class collection of validation errors (Object Calisthenics rule
 * 4: it wraps the array and nothing else). It is iterable and countable,
 * and renders itself for humans (describe/summary) or for serialization
 * (toArray) instead of exposing the raw list through getters.
 *
 * @implements IteratorAggregate<int,ValidationError>
 */
final class ValidationErrorBag implements Countable, IteratorAggregate
{
    /**
     * @var ValidationError[]
     */
    private $errors;

    /**
     * @param ValidationError[] $errors
     */
    public function __construct(array $errors = [])
    {
        $this->errors = array_values($errors);
    }

    public static function single(ValidationError $error): self
    {
        return new self([$error]);
    }

    public function withError(ValidationError $error): self
    {
        return new self(array_merge($this->errors, [$error]));
    }

    public function merge(self $other): self
    {
        return new self(array_merge($this->errors, $other->errors));
    }

    public function isEmpty(): bool
    {
        return $this->errors === [];
    }

    public function count(): int
    {
        return count($this->errors);
    }

    /**
     * @return Traversable<int,ValidationError>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->errors);
    }

    /**
     * One formatted "path: message" line per error, ready to display.
     *
     * @return string[]
     */
    public function describe(): array
    {
        return array_map(
            static function (ValidationError $error): string {
                return $error->describedAs();
            },
            $this->errors
        );
    }

    /**
     * @return array<int,array{path:string,message:string,code:string}>
     */
    public function toArray(): array
    {
        return array_map(
            static function (ValidationError $error): array {
                return $error->toArray();
            },
            $this->errors
        );
    }

    public function summary(): string
    {
        if ($this->errors === []) {
            return 'Validation failed';
        }

        $first = $this->errors[0]->describedAs();
        $remaining = count($this->errors) - 1;

        if ($remaining === 0) {
            return $first;
        }

        return sprintf('%s (and %d more error%s)', $first, $remaining, $remaining === 1 ? '' : 's');
    }
}
