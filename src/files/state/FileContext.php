<?php

namespace Arbor\files\state;


use Arbor\stream\StreamInterface;
use RuntimeException;
use LogicException;


final class FileContext
{
    public function __construct(
        private readonly ?StreamInterface $stream,
        private readonly ?string $path,
        private readonly ?string $name,
        private readonly ?string $mime,
        private readonly ?string $extension,
        private readonly ?int $size,
        private readonly ?bool $isBinary,
        private readonly ?string $hash,
        private readonly bool $proved,
        private array $metadata = [],
    ) {
        // Source invariant (always required)
        if ($path === null && $stream === null) {
            throw new RuntimeException(
                'FileContext must have either path or stream.'
            );
        }

        // Proven invariant
        if ($proved) {
            if (
                $mime === null ||
                $extension === null ||
                $size === null ||
                $isBinary === null
            ) {
                throw new RuntimeException(
                    'Proved FileContext must contain complete verified metadata.'
                );
            }
        }
    }


    public function withReplacedMeta(array $metadata): self
    {
        return new self(
            stream: $this->stream,
            path: $this->path,
            name: $this->name,
            mime: $this->mime,
            extension: $this->extension,
            size: $this->size,
            isBinary: $this->isBinary,
            hash: $this->hash,
            proved: $this->proved,
            metadata: $metadata,
        );
    }


    public function withMeta(string $key, mixed $value): self
    {
        $metadata = $this->metadata;
        value_set_at($metadata, $key, $value);

        return new self(
            stream: $this->stream,
            path: $this->path,
            name: $this->name,
            mime: $this->mime,
            extension: $this->extension,
            size: $this->size,
            isBinary: $this->isBinary,
            hash: $this->hash,
            proved: $this->proved,
            metadata: $metadata,
        );
    }


    public function getMeta(string $key): mixed
    {
        $value = value_at($this->metadata, $key, null);

        if ($value === null && !$this->has($key)) {
            throw new LogicException(
                'attribute not available: ' . $key
            );
        }

        return $value;
    }


    public function has(string $key): bool
    {
        return value_at($this->metadata, $key, '__missing__') !== '__missing__';
    }


    public function assertProved(): void
    {
        if (!$this->proved) {
            throw new RuntimeException(
                'FileContext is not yet proved.'
            );
        }
    }

    public function filename(): string
    {
        $this->assertProved();

        if ($this->name === null || $this->extension === null) {
            throw new RuntimeException(
                'Cannot build filename: name or extension missing.'
            );
        }

        return $this->name . '.' . $this->extension;
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

    public function isBinary(): bool
    {
        $this->assertProved();
        return $this->isBinary;
    }

    public function stream(): ?StreamInterface
    {
        return $this->stream;
    }

    public function path(): ?string
    {
        return $this->path;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function hash(): ?string
    {
        return $this->hash;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function inspectMime(): ?string
    {
        return $this->mime;
    }

    public function inspectExtension(): ?string
    {
        return $this->extension;
    }

    public function inspectSize(): ?int
    {
        return $this->size;
    }

    public function inspectBinary(): ?bool
    {
        return $this->isBinary;
    }

    public function isProved(): bool
    {
        return $this->proved;
    }
}
