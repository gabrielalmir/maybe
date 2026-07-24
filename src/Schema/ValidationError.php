<?php

declare(strict_types=1);

namespace Maybe\Schema;

/**
 * A single validation failure: where it happened (Path) and why (Reason).
 *
 * Following Object Calisthenics "Tell, Don't Ask", this object renders
 * itself (describedAs / toArray) and re-parents itself (underField /
 * underIndex) instead of exposing its internals through getters.
 */
final class ValidationError
{
    /**
     * @var Path
     */
    private $path;

    /**
     * @var Reason
     */
    private $reason;

    public function __construct(Path $path, string $message, string $code = 'invalid')
    {
        $this->path = $path;
        $this->reason = new Reason($message, $code);
    }

    public function underField(string $field): self
    {
        return $this->rewrap($this->path->underField($field));
    }

    public function underIndex(int $index): self
    {
        return $this->rewrap($this->path->underIndex($index));
    }

    public function describedAs(): string
    {
        return $this->path->toString() . ': ' . $this->reason->describedAs();
    }

    /**
     * @return array{path:string,message:string,code:string}
     */
    public function toArray(): array
    {
        $reason = $this->reason->toArray();

        return [
            'path' => $this->path->toString(),
            'message' => $reason['message'],
            'code' => $reason['code'],
        ];
    }

    private function rewrap(Path $path): self
    {
        $clone = clone $this;
        $clone->path = $path;

        return $clone;
    }
}
