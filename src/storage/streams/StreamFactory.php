<?php

namespace Arbor\storage\streams;


use RuntimeException;


final class StreamFactory
{
    public static function fromFile(string $path, string $mode = 'rb'): StreamInterface
    {
        $resource = fopen($path, $mode);

        if ($resource === false) {
            throw new RuntimeException("Unable to open file: {$path}");
        }

        return new Stream($resource);
    }

    public static function fromString(string $data): StreamInterface
    {
        $resource = fopen('php://temp', 'rb+');

        if ($resource === false) {
            throw new RuntimeException('Unable to create temp stream');
        }

        fwrite($resource, $data);
        rewind($resource);

        return new Stream($resource);
    }

    public static function fromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }

    public static function empty(): StreamInterface
    {
        $resource = fopen('php://temp', 'rb+');

        if ($resource === false) {
            throw new RuntimeException('Unable to create empty stream');
        }

        return new Stream($resource);
    }
}
