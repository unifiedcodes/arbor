<?php

namespace Arbor\files\ingress;

use Arbor\stream\StreamInterface;


final class Payload
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $mime,
        public readonly int $size,

        public readonly ?string $path = null,
        public readonly ?StreamInterface $stream = null,

        public readonly ?int $error = null,
        public readonly bool $moved = false,
        public readonly ?string $extension = null,
    ) {}
}
