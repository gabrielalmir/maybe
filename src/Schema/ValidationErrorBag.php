<?php

declare(strict_types=1);

namespace Maybe\Schema;

final class ValidationErrorBag
{
    /**
     * @var ValidationError[]
     */
    private $errors;

    /**
     * @param ValidationError[] $errors
     */
    public function __construct(array $errors = [])
    {
        $this->errors = array_values($errors);
    }

    public static function single(ValidationError $error): self
    {
        return new self([$error]);
    }

    public function withError(ValidationError $error): self
    {
        $errors = $this->errors;
        $errors[] = $error;

        return new self($errors);
    }

    public function merge(self $other): self
    {
        return new self(array_merge($this->errors, $other->errors));
    }

    public function isEmpty(): bool
    {
        return count($this->errors) === 0;
    }

    public function count(): int
    {
        return count($this->errors);
    }

    /**
     * @return ValidationError[]
     */
    public function all(): array
    {
        return $this->errors;
    }

    public function first(): ?ValidationError
    {
        if ($this->isEmpty()) {
            return null;
        }

        return $this->errors[0];
    }

    /**
     * @return array<int,array{path:string,message:string,code:string}>
     */
    public function toArray(): array
    {
        return array_map(
            static function (ValidationError $error): array {
                return $error->toArray();
            },
            $this->errors
        );
    }

    public function summary(): string
    {
        $first = $this->first();

        if ($first === null) {
            return 'Validation failed';
        }

        if ($this->count() === 1) {
            return sprintf('%s: %s', $first->path(), $first->message());
        }

        return sprintf(
            '%s: %s (and %d more error%s)',
            $first->path(),
            $first->message(),
            $this->count() - 1,
            ($this->count() - 1) === 1 ? '' : 's'
        );
    }
}

