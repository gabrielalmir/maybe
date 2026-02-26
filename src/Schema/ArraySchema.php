<?php

declare(strict_types=1);

namespace Maybe\Schema;

/**
 * @template T
 * @extends AbstractSchema<array<int,T>>
 */
final class ArraySchema extends AbstractSchema
{
    /**
     * @var SchemaInterface<T>
     */
    private $itemSchema;

    /**
     * @param SchemaInterface<T> $itemSchema
     */
    public function __construct(SchemaInterface $itemSchema)
    {
        $this->itemSchema = $itemSchema;
    }

    /**
     * @param mixed $input
     * @return array<int,T>
     */
    public function parse($input): array
    {
        if (!is_array($input)) {
            throw new ValidationException(
                ValidationErrorBag::single(new ValidationError('$', 'Expected array', 'type.array'))
            );
        }

        $errors = new ValidationErrorBag();
        $parsed = [];

        foreach (array_values($input) as $index => $value) {
            try {
                $parsed[] = $this->itemSchema->parse($value);
            } catch (ValidationException $e) {
                foreach ($e->errors()->all() as $error) {
                    $errors = $errors->withError(
                        new ValidationError(
                            sprintf('[%d]%s', $index, $this->normalizePath($error->path())),
                            $error->message(),
                            $error->code()
                        )
                    );
                }
            }
        }

        if (!$errors->isEmpty()) {
            throw new ValidationException($errors);
        }

        return $parsed;
    }

    private function normalizePath(string $path): string
    {
        return $path === '$' ? '' : '.' . ltrim($path, '$.');
    }
}
