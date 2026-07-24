<?php

declare(strict_types=1);

namespace Maybe\Schema;

/**
 * @extends AbstractSchema<string>
 */
final class StringSchema extends AbstractSchema
{
    /**
     * @var TextLength
     */
    private $length;

    /**
     * @var TextFormat
     */
    private $format;

    public function __construct(?TextLength $length = null, ?TextFormat $format = null)
    {
        $this->length = $length ?? TextLength::any();
        $this->format = $format ?? TextFormat::raw();
    }

    public function trimmed(): self
    {
        return new self($this->length, $this->format->trimmed());
    }

    public function min(int $minLength): self
    {
        return new self($this->length->withMin($minLength), $this->format);
    }

    public function max(int $maxLength): self
    {
        return new self($this->length->withMax($maxLength), $this->format);
    }

    public function regex(string $pattern): self
    {
        return new self($this->length, $this->format->withPattern($pattern));
    }

    /**
     * @param mixed $input
     * @return string
     */
    public function parse($input): string
    {
        $this->rejectNonString($input);

        $value = $this->format->normalize($input);

        $this->length->validate($value);
        $this->format->validate($value);

        return $value;
    }

    /**
     * @param mixed $input
     */
    private function rejectNonString($input): void
    {
        if (!is_string($input)) {
            throw new ValidationException(
                ValidationErrorBag::single(new ValidationError(Path::root(), 'Expected string', 'type.string'))
            );
        }
    }
}
