<?php

declare(strict_types=1);

namespace Maybe\Schema;

/**
 * The optional min/max length constraint of a string, kept together so
 * StringSchema stays at two instance variables (Object Calisthenics rule
 * 8) and validates length in one place.
 */
final class TextLength
{
    /**
     * @var int|null
     */
    private $min;

    /**
     * @var int|null
     */
    private $max;

    public function __construct(?int $min = null, ?int $max = null)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public static function any(): self
    {
        return new self();
    }

    public function withMin(int $min): self
    {
        return new self($min, $this->max);
    }

    public function withMax(int $max): self
    {
        return new self($this->min, $max);
    }

    public function validate(string $value): void
    {
        $length = $this->measure($value);

        if ($this->min !== null && $length < $this->min) {
            throw new ValidationException(
                ValidationErrorBag::single(
                    new ValidationError(Path::root(), sprintf('String must have at least %d characters', $this->min), 'string.min')
                )
            );
        }

        if ($this->max !== null && $length > $this->max) {
            throw new ValidationException(
                ValidationErrorBag::single(
                    new ValidationError(Path::root(), sprintf('String must have at most %d characters', $this->max), 'string.max')
                )
            );
        }
    }

    private function measure(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }
}
