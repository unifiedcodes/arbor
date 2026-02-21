<?php

namespace Arbor\files\state;


use Arbor\stream\StreamInterface;
use InvalidArgumentException;


final class Payload
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $mime,
        public readonly ?int $size = null,
        public readonly ?string $extension = null,
        public readonly ?string $path = null,
        public readonly ?StreamInterface $stream = null,
        public readonly ?string $hash = null,
    ) {
        if ($this->path === null && $this->stream === null) {
            throw new InvalidArgumentException(
                'Either path or stream must be provided.'
            );
        }
    }
}
