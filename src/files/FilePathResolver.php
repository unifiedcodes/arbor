<?php

namespace Arbor\files;


use RuntimeException;
use Arbor\http\components\Stream;


final class FilePathResolver
{
    public static function resolve(mixed $source): string
    {
        if (is_string($source)) {
            return $source;
        }

        if (is_resource($source)) {
            return self::fromResource($source);
        }

        if ($source instanceof Stream) {
            return self::fromStream($source);
        }

        throw new RuntimeException('Unresolvable file source');
    }


    private static function fromResource($resource): string
    {
        $path = tempnam(sys_get_temp_dir(), 'upl_');
        file_put_contents($path, stream_get_contents($resource));
        return $path;
    }


    private static function fromStream(Stream $stream): string
    {
        $path = tempnam(sys_get_temp_dir(), 'upl_');
        file_put_contents($path, $stream->getContents());
        return $path;
    }
}
