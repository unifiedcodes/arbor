<?php

namespace Arbor\stream;

use Arbor\stream\StreamInterface;
use RuntimeException;


final class StreamFactory
{
    public static function fromFile(string $path): StreamInterface
    {
        $resource = fopen($path, 'rb');

        if ($resource === false) {
            throw new RuntimeException("Unable to open file: {$path}");
        }

        return new Stream($resource);
    }


    public static function fromString(string $buffer): StreamInterface
    {
        $resource = fopen('php://temp', 'rb+');

        if ($resource === false) {
            throw new RuntimeException('Unable to create memory stream');
        }

        fwrite($resource, $buffer);
        rewind($resource);

        return new Stream($resource);
    }


    public static function fromPhpInput(): StreamInterface
    {
        $resource = fopen('php://input', 'rb');

        if ($resource === false) {
            throw new RuntimeException('Unable to open php://input');
        }

        return new Stream($resource);
    }


    public static function fromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }


    public static function toRewindable(StreamInterface $stream): StreamInterface
    {
        // rewindable stream, return after rewind.
        if ($stream->isSeekable()) {
            $stream->rewind();
            return $stream;
        }

        // non - rewindable stream, must start from 0.

        $pos = $stream->tell();

        if ($pos !== false && $pos !== 0) {
            throw new RuntimeException('Cannot make stream rewindable after it has been partially read');
        }

        $resource = fopen('php://temp', 'wb+');

        if ($resource === false) {
            throw new RuntimeException('Unable to create temporary stream');
        }

        while (!$stream->eof()) {
            fwrite($resource, $stream->read(8192));
        }

        rewind($resource);

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
