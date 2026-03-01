<?php

declare(strict_types=1);

namespace Maybe\Schema;

use DateTimeImmutable;

/**
 * @extends AbstractSchema<DateTimeImmutable>
 */
final class DateSchema extends AbstractSchema
{
    /**
     * @var string
     */
    private $format;

    /**
     * @var DateTimeImmutable|null
     */
    private $min;

    /**
     * @var DateTimeImmutable|null
     */
    private $max;

    public function __construct(string $format = 'Y-m-d', ?DateTimeImmutable $min = null, ?DateTimeImmutable $max = null)
    {
        $this->format = $format;
        $this->min = $min;
        $this->max = $max;
    }

    public function format(string $format): self
    {
        return new self($format, $this->min, $this->max);
    }

    public function min(DateTimeImmutable $value): self
    {
        return new self($this->format, $value, $this->max);
    }

    public function max(DateTimeImmutable $value): self
    {
        return new self($this->format, $this->min, $value);
    }

    /**
     * @param mixed $input
     * @return DateTimeImmutable
     */
    public function parse($input): DateTimeImmutable
    {
        if (!is_string($input)) {
            throw new ValidationException(
                ValidationErrorBag::single(new ValidationError('$', 'Expected date string', 'type.date'))
            );
        }

        $parsed = DateTimeImmutable::createFromFormat('!' . $this->format, $input);

        if ($parsed === false || $parsed->format($this->format) !== $input) {
            throw new ValidationException(
                ValidationErrorBag::single(
                    new ValidationError(
                        '$',
                        sprintf('Expected date in format %s', $this->format),
                        'date.format'
                    )
                )
            );
        }

        if ($this->min !== null && $parsed < $this->min) {
            throw new ValidationException(
                ValidationErrorBag::single(
                    new ValidationError(
                        '$',
                        sprintf('Date must be on or after %s', $this->min->format($this->format)),
                        'date.min'
                    )
                )
            );
        }

        if ($this->max !== null && $parsed > $this->max) {
            throw new ValidationException(
                ValidationErrorBag::single(
                    new ValidationError(
                        '$',
                        sprintf('Date must be on or before %s', $this->max->format($this->format)),
                        'date.max'
                    )
                )
            );
        }

        return $parsed;
    }
}
