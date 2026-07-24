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
     * @var DateBounds
     */
    private $bounds;

    public function __construct(string $format = 'Y-m-d', ?DateBounds $bounds = null)
    {
        $this->format = $format;
        $this->bounds = $bounds ?? DateBounds::none();
    }

    public function format(string $format): self
    {
        return new self($format, $this->bounds);
    }

    public function min(DateTimeImmutable $value): self
    {
        return new self($this->format, $this->bounds->withMin($value));
    }

    public function max(DateTimeImmutable $value): self
    {
        return new self($this->format, $this->bounds->withMax($value));
    }

    /**
     * @param mixed $input
     * @return DateTimeImmutable
     */
    public function parse($input): DateTimeImmutable
    {
        $this->rejectNonString($input);

        $parsed = $this->parseFormatted($input);

        $this->bounds->validate($parsed, $this->format);

        return $parsed;
    }

    /**
     * @param mixed $input
     */
    private function rejectNonString($input): void
    {
        if (!is_string($input)) {
            throw new ValidationException(
                ValidationErrorBag::single(new ValidationError(Path::root(), 'Expected date string', 'type.date'))
            );
        }
    }

    private function parseFormatted(string $input): DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!' . $this->format, $input);

        if ($parsed === false || $parsed->format($this->format) !== $input) {
            throw new ValidationException(
                ValidationErrorBag::single(
                    new ValidationError(Path::root(), sprintf('Expected date in format %s', $this->format), 'date.format')
                )
            );
        }

        return $parsed;
    }
}
