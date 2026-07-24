<?php

declare(strict_types=1);

namespace Maybe\Schema;

/**
 * Why a value was rejected: a human-readable message plus a stable machine
 * code. Wrapping the two together keeps ValidationError down to two
 * instance variables (Object Calisthenics rule 8) and gives the pair a
 * single place to render itself.
 */
final class Reason
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $code;

    public function __construct(string $message, string $code = 'invalid')
    {
        $this->message = $message;
        $this->code = $code;
    }

    public function describedAs(): string
    {
        return $this->message;
    }

    /**
     * @return array{message:string,code:string}
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'code' => $this->code,
        ];
    }
}
