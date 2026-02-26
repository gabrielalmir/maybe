<?php

declare(strict_types=1);

namespace Maybe\Schema;

use Maybe\Exception\LogicException;

final class ValidationException extends LogicException
{
    /**
     * @var ValidationErrorBag
     */
    private $errors;

    public function __construct(ValidationErrorBag $errors)
    {
        $this->errors = $errors;
        parent::__construct($errors->summary());
    }

    public function errors(): ValidationErrorBag
    {
        return $this->errors;
    }
}

