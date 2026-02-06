<?php

namespace Arbor\files;


use Arbor\files\Payload;


final class FileContext
{
    private function __construct(
        private Payload $payload,
        private array $attributes = []
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        return new self($payload, [
            'name' => $payload->originalName,
            'mime' => $payload->mime,
            'size' => $payload->size,
        ]);
    }

    public function payload(): Payload
    {
        return $this->payload;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function with(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->attributes[$key] = $value;
        return $clone;
    }

    public function all(): array
    {
        return $this->attributes;
    }
}
