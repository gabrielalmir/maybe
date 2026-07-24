<?php

declare(strict_types=1);

namespace Maybe\Schema;

/**
 * A JSONPath-like location of a validation error (e.g. "$", "$.email",
 * "$[1].email"). Wraps the path string (Object Calisthenics rule 3) and
 * owns the composition logic that previously lived as string juggling
 * inside ObjectSchema/ArraySchema.
 */
final class Path
{
    /**
     * @var string
     */
    private $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function root(): self
    {
        return new self('$');
    }

    /**
     * Re-parent this path under an object field, e.g. "$" under "email"
     * becomes "$.email"; "$.city" under "address" becomes "$.address.city".
     */
    public function underField(string $field): self
    {
        if ($this->value === '$') {
            return new self('$.' . $field);
        }

        return new self('$.' . $field . '.' . ltrim($this->value, '$.'));
    }

    /**
     * Re-parent this path under an array index, e.g. "$" under index 1
     * becomes "$[1]"; "$.email" under index 1 becomes "$[1].email".
     */
    public function underIndex(int $index): self
    {
        return new self('$[' . $index . ']' . $this->suffix());
    }

    public function toString(): string
    {
        return $this->value;
    }

    private function suffix(): string
    {
        if ($this->value === '$') {
            return '';
        }

        $relative = ltrim($this->value, '$');

        if (strpos($relative, '[') === 0) {
            return $relative;
        }

        return '.' . ltrim($relative, '.');
    }
}
