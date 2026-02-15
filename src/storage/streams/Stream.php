<?php

namespace Arbor\storage\streams;


use RuntimeException;


final class Stream implements StreamInterface
{
    private $resource;

    public function __construct($resource)
    {
        if (!is_resource($resource)) {
            throw new RuntimeException('Invalid stream resource');
        }

        $this->resource = $resource;
    }


    public function resource()
    {
        return $this->resource;
    }


    public function read(int $length): string
    {
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
        return feof($this->resource);
    }


    public function close(): void
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }


    public function isReadable(): bool
    {
        $mode = stream_get_meta_data($this->resource)['mode'];

        return strpbrk($mode, 'r+') !== false;
    }


    public function isWritable(): bool
    {
        $mode = stream_get_meta_data($this->resource)['mode'];

        return strpbrk($mode, 'waxc+') !== false;
    }
}
