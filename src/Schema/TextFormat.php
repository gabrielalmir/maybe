<?php

declare(strict_types=1);

namespace Maybe\Schema;

/**
 * How a string is normalized (trimming) and constrained (an optional regex
 * pattern). Bundled so StringSchema stays at two instance variables
 * (Object Calisthenics rule 8).
 */
final class TextFormat
{
    /**
     * @var bool
     */
    private $trim;

    /**
     * @var string|null
     */
    private $pattern;

    public function __construct(bool $trim = false, ?string $pattern = null)
    {
        $this->trim = $trim;
        $this->pattern = $pattern;
    }

    public static function raw(): self
    {
        return new self();
    }

    public function trimmed(): self
    {
        return new self(true, $this->pattern);
    }

    public function withPattern(string $pattern): self
    {
        return new self($this->trim, $pattern);
    }

    public function normalize(string $input): string
    {
        return $this->trim ? trim($input) : $input;
    }

    public function validate(string $value): void
    {
        if ($this->pattern === null) {
            return;
        }

        $matched = preg_match($this->pattern, $value);

        if ($matched === false) {
            throw new ValidationException(
                ValidationErrorBag::single(new ValidationError(Path::root(), 'Invalid regex pattern in schema', 'string.pattern.invalid'))
            );
        }

        if ($matched !== 1) {
            throw new ValidationException(
                ValidationErrorBag::single(new ValidationError(Path::root(), 'String does not match expected format', 'string.pattern'))
            );
        }
    }
}
