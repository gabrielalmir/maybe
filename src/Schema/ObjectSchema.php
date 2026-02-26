<?php

declare(strict_types=1);

namespace Maybe\Schema;

/**
 * @extends AbstractSchema<array<string,mixed>>
 */
final class ObjectSchema extends AbstractSchema
{
    /**
     * @var array<string,SchemaInterface<mixed>>
     */
    private $shape;

    /**
     * @var bool
     */
    private $allowUnknown;

    /**
     * @param array<string,SchemaInterface<mixed>> $shape
     */
    public function __construct(array $shape, bool $allowUnknown = false)
    {
        $this->shape = $shape;
        $this->allowUnknown = $allowUnknown;
    }

    public function allowUnknown(): self
    {
        return new self($this->shape, true);
    }

    /**
     * @param mixed $input
     * @return array<string,mixed>
     */
    public function parse($input): array
    {
        if (!is_array($input)) {
            throw new ValidationException(
                ValidationErrorBag::single(new ValidationError('$', 'Expected object-like array', 'type.object'))
            );
        }

        $errors = new ValidationErrorBag();
        $output = [];

        foreach ($this->shape as $key => $schema) {
            if (!array_key_exists($key, $input)) {
                $errors = $errors->withError(new ValidationError($key, 'Missing required field', 'object.missing'));
                continue;
            }

            try {
                $output[$key] = $schema->parse($input[$key]);
            } catch (ValidationException $e) {
                foreach ($e->errors()->all() as $error) {
                    $errors = $errors->withError(
                        new ValidationError(
                            $this->composePath($key, $error->path()),
                            $error->message(),
                            $error->code()
                        )
                    );
                }
            }
        }

        if (!$this->allowUnknown) {
            foreach ($input as $key => $value) {
                if (!array_key_exists((string) $key, $this->shape)) {
                    $errors = $errors->withError(
                        new ValidationError((string) $key, 'Unknown field is not allowed', 'object.unknown')
                    );
                }
            }
        } else {
            foreach ($input as $key => $value) {
                if (!array_key_exists((string) $key, $output)) {
                    $output[(string) $key] = $value;
                }
            }
        }

        if (!$errors->isEmpty()) {
            throw new ValidationException($errors);
        }

        return $output;
    }

    private function composePath(string $field, string $path): string
    {
        if ($path === '$') {
            return $field;
        }

        return $field . '.' . ltrim($path, '$.');
    }
}
