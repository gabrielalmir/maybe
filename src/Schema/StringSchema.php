<?php

declare(strict_types=1);

namespace Maybe\Schema;

/**
 * @extends AbstractSchema<string>
 */
final class StringSchema extends AbstractSchema
{
    /**
     * @var bool
     */
    private $trim;

    /**
     * @var int|null
     */
    private $minLength;

    /**
     * @var int|null
     */
    private $maxLength;

    /**
     * @var string|null
     */
    private $pattern;

    public function __construct(bool $trim = false, ?int $minLength = null, ?int $maxLength = null, ?string $pattern = null)
    {
        $this->trim = $trim;
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->pattern = $pattern;
    }

    public function trimmed(): self
    {
        return new self(true, $this->minLength, $this->maxLength, $this->pattern);
    }

    public function min(int $minLength): self
    {
        return new self($this->trim, $minLength, $this->maxLength, $this->pattern);
    }

    public function max(int $maxLength): self
    {
        return new self($this->trim, $this->minLength, $maxLength, $this->pattern);
    }

    public function regex(string $pattern): self
    {
        return new self($this->trim, $this->minLength, $this->maxLength, $pattern);
    }

    /**
     * @param mixed $input
     * @return string
     */
    public function parse($input): string
    {
        if (!is_string($input)) {
            throw new ValidationException(
                ValidationErrorBag::single(new ValidationError('$', 'Expected string', 'type.string'))
            );
        }

        $value = $this->trim ? trim($input) : $input;

        if ($this->minLength !== null && strlen($value) < $this->minLength) {
            throw new ValidationException(
                ValidationErrorBag::single(
                    new ValidationError('$', sprintf('String must have at least %d characters', $this->minLength), 'string.min')
                )
            );
        }

        if ($this->maxLength !== null && strlen($value) > $this->maxLength) {
            throw new ValidationException(
                ValidationErrorBag::single(
                    new ValidationError('$', sprintf('String must have at most %d characters', $this->maxLength), 'string.max')
                )
            );
        }

        if ($this->pattern !== null && preg_match($this->pattern, $value) !== 1) {
            throw new ValidationException(
                ValidationErrorBag::single(new ValidationError('$', 'String does not match expected format', 'string.pattern'))
            );
        }

        return $value;
    }
}

