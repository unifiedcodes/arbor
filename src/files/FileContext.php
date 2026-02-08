<?php

namespace Arbor\files;


use Arbor\files\Payload;
use LogicException;


final class FileContext
{
    private Payload $payload;
    private string $mime;
    private string $extension;
    private int $size;
    private string $hash;
    private string $path;
    private bool $binary;
    private bool $proved = false;
    private array $attributes = [];
    private ?string $name = null;


    public static function fromPayload(Payload $payload): self
    {
        $ctx = new self();
        $ctx->payload = $payload;
        return $ctx;
    }

    public function getPayload(): Payload
    {
        return $this->payload;
    }

    public function claimMime(): ?string
    {
        return $this->payload->mime;
    }

    public function claimExtension(): ?string
    {
        return $this->payload->extension;
    }

    public function originalName(): string
    {
        return $this->payload->name;
    }

    public function claimSize(): int
    {
        return $this->payload->size;
    }

    public function normalize(
        string $mime,
        string $extension,
        int $size,
        string $path,
        string $hash,
        bool $binary,
    ): self {
        if ($this->proved) {
            throw new LogicException('FileContext is already normalized');
        }

        $clone = clone $this;

        $clone->mime = $mime;
        $clone->extension = $extension;
        $clone->size = $size;
        $clone->path = $path;
        $clone->hash = $hash;
        $clone->binary = $binary;

        $clone->name = pathinfo($this->originalName(), PATHINFO_FILENAME);
        $clone->proved = true;

        return $clone;
    }

    public function withName(string $name): self
    {
        $this->assertProved();

        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    public function isProved(): bool
    {
        return $this->proved;
    }

    private function assertProved(): void
    {
        if (!$this->proved) {
            throw new LogicException('FileContext is not proved yet');
        }
    }

    public function name(): string
    {
        $this->assertProved();
        return $this->name;
    }

    public function mime(): string
    {
        $this->assertProved();
        return $this->mime;
    }

    public function extension(): string
    {
        $this->assertProved();
        return $this->extension;
    }

    public function size(): int
    {
        $this->assertProved();
        return $this->size;
    }

    public function path(): string
    {
        $this->assertProved();
        return $this->path;
    }

    public function hash(): string
    {
        $this->assertProved();
        return $this->hash;
    }

    public function isBinary(): bool
    {
        $this->assertProved();
        return $this->binary;
    }

    public function set(string $key, mixed $value): void
    {
        value_set_at($this->attributes, $key, $value);
    }

    public function get(string $key): mixed
    {
        $value = value_at($this->attributes, $key, null);

        if ($value === null && !$this->has($key)) {
            throw new LogicException(
                'attribute not available: ' . $key
            );
        }

        return $value;
    }

    public function has(string $key): bool
    {
        return value_at($this->attributes, $key, '__missing__') !== '__missing__';
    }
}
