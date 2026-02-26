<?php

declare(strict_types=1);

namespace Maybe\Schema;

/**
 * @extends AbstractSchema<bool>
 */
final class BoolSchema extends AbstractSchema
{
    /**
     * @param mixed $input
     * @return bool
     */
    public function parse($input): bool
    {
        if (!is_bool($input)) {
            throw new ValidationException(
                ValidationErrorBag::single(new ValidationError('$', 'Expected bool', 'type.bool'))
            );
        }

        return $input;
    }
}
