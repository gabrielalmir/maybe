<?php

declare(strict_types=1);

namespace Maybe\Schema;

/**
 * @extends AbstractSchema<int>
 */
final class IntSchema extends AbstractSchema
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

    public function min(int $value): self
    {
        return new self($value, $this->max);
    }

    public function max(int $value): self
    {
        return new self($this->min, $value);
    }

    /**
     * @param mixed $input
     * @return int
     */
    public function parse($input): int
    {
        if (!is_int($input)) {
            throw new ValidationException(
                ValidationErrorBag::single(new ValidationError('$', 'Expected int', 'type.int'))
            );
        }

        if ($this->min !== null && $input < $this->min) {
            throw new ValidationException(
                ValidationErrorBag::single(new ValidationError('$', sprintf('Value must be >= %d', $this->min), 'int.min'))
            );
        }

        if ($this->max !== null && $input > $this->max) {
            throw new ValidationException(
                ValidationErrorBag::single(new ValidationError('$', sprintf('Value must be <= %d', $this->max), 'int.max'))
            );
        }

        return $input;
    }
}
