<?php

namespace Arbor\stream;

use Arbor\stream\StreamInterface;
use RuntimeException;


final class Stream implements StreamInterface
{
    private $resource;
    private bool $readable;
    private bool $writable;
    private bool $seekable;


    public function __construct($resource)
    {
        if (!is_resource($resource)) {
            throw new RuntimeException('Invalid stream resource');
        }

        $meta = stream_get_meta_data($resource);

        if (!isset($meta['mode'])) {
            throw new RuntimeException('Invalid stream resource (no mode)');
        }

        $this->readable = strpbrk($meta['mode'], 'r+') !== false;
        $this->writable = strpbrk($meta['mode'], 'waxc+') !== false;
        $this->seekable = (bool) ($meta['seekable'] ?? false);

        $this->resource = $resource;
    }


    public function read(int $length): string
    {
        $this->assertAttached();

        if ($length < 1) {
            return '';
        }

        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable');
        }

        $data = fread($this->resource, $length);

        if ($data === false) {
            throw new RuntimeException('Failed to read from stream');
        }

        return $data;
    }


    public function write(string $bytes): int
    {
        $this->assertAttached();


        if (!$this->isWritable()) {
            throw new RuntimeException('Stream is not writable');
        }

        $written = fwrite($this->resource, $bytes);

        if ($written === false) {
            throw new RuntimeException('Failed to write to stream');
        }

        return $written;
    }


    public function eof(): bool
    {
        $this->assertAttached();

        return feof($this->resource);
    }


    public function close(): void
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }

        $this->resource = null;
    }


    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;

        return $resource;
    }


    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }


    public function tell(): ?int
    {
        $this->assertAttached();
        return ftell($this->resource);
    }


    public function rewind(): void
    {
        $this->assertAttached();

        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable');
        }

        rewind($this->resource);
    }


    private function assertAttached(): void
    {
        if (!is_resource($this->resource)) {
            throw new RuntimeException('Stream is detached or closed');
        }
    }
}
