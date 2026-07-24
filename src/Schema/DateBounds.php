<?php

declare(strict_types=1);

namespace Maybe\Schema;

use DateTimeImmutable;

/**
 * The optional earliest/latest boundary of a date, kept together so
 * DateSchema stays at two instance variables (Object Calisthenics rule 8).
 * Boundaries are normalized to the schema's format before comparison so
 * only the significant part of the date is considered.
 */
final class DateBounds
{
    /**
     * @var DateTimeImmutable|null
     */
    private $min;

    /**
     * @var DateTimeImmutable|null
     */
    private $max;

    public function __construct(?DateTimeImmutable $min = null, ?DateTimeImmutable $max = null)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public static function none(): self
    {
        return new self();
    }

    public function withMin(DateTimeImmutable $min): self
    {
        return new self($min, $this->max);
    }

    public function withMax(DateTimeImmutable $max): self
    {
        return new self($this->min, $max);
    }

    public function validate(DateTimeImmutable $parsed, string $format): void
    {
        $min = $this->normalize($this->min, $format);

        if ($min !== null && $parsed < $min) {
            throw new ValidationException(
                ValidationErrorBag::single(
                    new ValidationError(Path::root(), sprintf('Date must be on or after %s', $min->format($format)), 'date.min')
                )
            );
        }

        $max = $this->normalize($this->max, $format);

        if ($max !== null && $parsed > $max) {
            throw new ValidationException(
                ValidationErrorBag::single(
                    new ValidationError(Path::root(), sprintf('Date must be on or before %s', $max->format($format)), 'date.max')
                )
            );
        }
    }

    private function normalize(?DateTimeImmutable $boundary, string $format): ?DateTimeImmutable
    {
        if ($boundary === null) {
            return null;
        }

        $normalized = DateTimeImmutable::createFromFormat('!' . $format, $boundary->format($format));

        return $normalized === false ? $boundary : $normalized;
    }
}
