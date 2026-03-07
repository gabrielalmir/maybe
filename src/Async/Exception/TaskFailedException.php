<?php

declare(strict_types=1);

namespace Maybe\Async\Exception;

class TaskFailedException extends AsyncException
{
    /** @var string */
    private $remoteClass;

    /** @var string */
    private $remoteTrace;

    public function __construct(string $message, string $remoteClass = 'RuntimeException', int $code = 0, string $remoteTrace = '')
    {
        parent::__construct($message, $code);
        $this->remoteClass = $remoteClass;
        $this->remoteTrace = $remoteTrace;
    }

    public function remoteClass(): string
    {
        return $this->remoteClass;
    }

    public function remoteTrace(): string
    {
        return $this->remoteTrace;
    }
}
