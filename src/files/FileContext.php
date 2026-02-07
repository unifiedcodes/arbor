<?php

namespace Arbor\files;


use Arbor\files\Payload;
use Arbor\files\FileNormalized;


final class FileContext
{
    private function __construct(
        private Payload $payload,
        private array $attributes = [],
        private ?FileNormalized $normalized = null,
        private bool $proved = false
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


    /* --------------------Normalization-------------------- */

    public function withNormalized(FileNormalized $file): self
    {
        $clone = clone $this;
        $clone->normalized = $file;
        return $clone;
    }


    public function normalized(): FileNormalized
    {
        if (!$this->normalized) {
            throw new \LogicException('File not normalized');
        }
        return $this->normalized;
    }


    public function isNormalized(): bool
    {
        return $this->normalized !== null;
    }


    /* --------------------Proved state-------------------- */

    public function isProved(): bool
    {
        return $this->proved;
    }

    public function markProved(): self
    {
        if ($this->proved) {
            return $this; // idempotent
        }

        $clone = clone $this;
        $clone->proved = true;
        return $clone;
    }
}
