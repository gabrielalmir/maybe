<?php

declare(strict_types=1);

namespace Maybe\Schema;

final class ValidationError
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $code;

    public function __construct(string $path, string $message, string $code = 'invalid')
    {
        $this->path = $path;
        $this->message = $message;
        $this->code = $code;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function code(): string
    {
        return $this->code;
    }

    /**
     * @return array{path:string,message:string,code:string}
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'message' => $this->message,
            'code' => $this->code,
        ];
    }
}

