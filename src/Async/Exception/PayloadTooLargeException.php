<?php

declare(strict_types=1);

namespace Maybe\Async\Exception;

final class PayloadTooLargeException extends AsyncException
{
    /** @var string */
    private $direction;

    /** @var int */
    private $limit;

    public function __construct(string $direction, int $limit)
    {
        $this->direction = $direction;
        $this->limit = $limit;

        parent::__construct(sprintf('Async %s payload exceeds the configured limit of %d bytes', $direction, $limit));
    }

    public function direction(): string
    {
        return $this->direction;
    }

    public function limit(): int
    {
        return $this->limit;
    }
}
