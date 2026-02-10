<?php

namespace Arbor\files\ingress;


use RuntimeException;
use Arbor\http\components\Stream;


final class IngressNormalizer
{
    public static function getPath(mixed $input): string
    {
        if (is_string($input)) {
            if (!is_file($input)) {
                throw new RuntimeException("Invalid file path: {$input}");
            }

            return $input;
        }

        // resource → stream → temp path
        if (is_resource($input)) {
            $input = self::resourceToStream($input);
        }

        if ($input instanceof Stream) {
            return self::streamToTempPath($input);
        }

        throw new RuntimeException('Unsupported ingress input for getPath');
    }

    public static function getStream(mixed $input): Stream
    {
        if ($input instanceof Stream) {
            return $input;
        }

        if (is_resource($input)) {
            return self::resourceToStream($input);
        }

        if (is_string($input)) {
            if (!is_file($input)) {
                throw new RuntimeException("Invalid file path: {$input}");
            }

            $resource = fopen($input, 'rb');

            if ($resource === false) {
                throw new RuntimeException("Unable to open file: {$input}");
            }

            return new Stream($resource);
        }

        throw new RuntimeException('Unsupported ingress input for getStream');
    }

    private static function resourceToStream($resource): Stream
    {
        if (!is_resource($resource)) {
            throw new RuntimeException('Expected a valid resource');
        }

        return new Stream($resource);
    }

    private static function streamToTempPath(Stream $stream): string
    {
        // If stream is seekable, rewind to avoid partial reads
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $path = tempnam(sys_get_temp_dir(), 'upl_');

        if ($path === false) {
            throw new RuntimeException('Failed to create temporary file');
        }

        file_put_contents($path, $stream->getContents());

        return $path;
    }
}
