<?php

declare(strict_types=1);

namespace Maybe\Schema;

/**
 * @template T
 * @extends AbstractSchema<T>
 */
final class EnumSchema extends AbstractSchema
{
  /**
   * @var array<int,T>
   */
  private $allowedValues;

  /**
   * @param array<int,T> $allowedValues
   */
  public function __construct(array $allowedValues)
  {
    if ($allowedValues === []) {
      throw new \InvalidArgumentException('Enum schema requires at least one allowed value');
    }

    $this->allowedValues = array_values($allowedValues);
  }

  /**
   * @param mixed $input
   * @return T
   */
  public function parse($input)
  {
    if (!in_array($input, $this->allowedValues, true)) {
      throw new ValidationException(
        ValidationErrorBag::single(
          new ValidationError(
            '$',
            sprintf('Expected one of: %s', $this->stringifyAllowedValues()),
            'enum.invalid',
          ),
        ),
      );
    }

    /** @var T $input */
    return $input;
  }

  private function stringifyAllowedValues(): string
  {
    $formatted = array_map(static function ($value): string {
      if (is_string($value)) {
        return '"' . $value . '"';
      }

      if (is_bool($value)) {
        return $value ? 'true' : 'false';
      }

      if ($value === null) {
        return 'null';
      }

      if (is_scalar($value)) {
        return (string) $value;
      }

      return gettype($value);
    }, $this->allowedValues);

    return implode(', ', $formatted);
  }
}
