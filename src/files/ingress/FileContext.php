<?php

namespace Arbor\files\ingress;


use Arbor\files\ingress\Payload;
use Arbor\stream\StreamFactory;
use Arbor\stream\StreamInterface;
use LogicException;


final class FileContext
{
    private Payload $payload;
    private string $mime;
    private string $extension;
    private int $size;
    private string $hash;
    private bool $binary;
    private bool $proved = false;
    private array $attributes = [];
    private ?string $name = null;
    private ?string $materializedPath = null;


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
        string $hash,
        bool $binary,
        ?string $path = null,
        ?StreamInterface $stream = null,
    ): self {
        if ($this->proved) {
            throw new LogicException('FileContext is already normalized');
        }

        $clone = clone $this;

        // replace source ONLY if a new one is provided
        if ($path !== null || $stream !== null) {

            // ingress boundary: discard ingress source
            if ($this->payload->stream) {
                $this->payload->stream->close();
            }

            $clone->payload = new Payload(
                name: $this->payload->name,
                mime: $mime,
                size: $size,
                path: $path,
                stream: $stream,
                error: null,
                moved: true,
            );

            $clone->materializedPath = null;
        }

        // finalize semantic proof
        $clone->mime = $mime;
        $clone->extension = $extension;
        $clone->size = $size;
        $clone->hash = $hash;
        $clone->binary = $binary;
        $clone->name = self::deriveBaseName($this->originalName());
        $clone->proved = true;

        return $clone;
    }


    private static function deriveBaseName(string $original): string
    {
        return pathinfo($original, PATHINFO_FILENAME);
    }


    public function withName(string $name): self
    {
        $this->assertProved();

        // enforce invariant: logical name only
        if (str_contains($name, '.')) {
            throw new LogicException(
                'Name must not contain an extension'
            );
        }

        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }


    public function filename(): string
    {
        $this->assertProved();
        return $this->name . '.' . $this->extension;
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

    public function hasPath(): bool
    {
        return $this->payload->path !== null;
    }

    public function hasStream(): bool
    {
        return $this->payload->stream !== null;
    }

    public function stream(): StreamInterface
    {
        if ($this->payload->stream) {
            return $this->payload->stream;
        }

        if ($this->payload->path) {
            return StreamFactory::fromFile($this->payload->path);
        }

        throw new LogicException('No stream available');
    }


    public function materialize(): string
    {
        if ($this->payload->path) {
            return $this->payload->path;
        }

        if ($this->materializedPath) {
            return $this->materializedPath;
        }

        if (!$this->payload->stream) {
            throw new LogicException('Cannot materialize without stream');
        }

        if (!$this->payload->stream->isSeekable()) {
            throw new LogicException('Cannot materialize non-seekable stream');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'ing_');
        $out = fopen($tmp, 'wb');

        $in = $this->payload->stream->resource();
        $this->payload->stream->rewind();

        stream_copy_to_stream($in, $out);
        fclose($out);

        $this->materializedPath = $tmp;

        return $tmp;
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
