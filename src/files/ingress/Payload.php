<?php

namespace Arbor\files\ingress;

use Arbor\stream\StreamInterface;

final class Payload
{
    public function __construct(
        public readonly string  $name,
        public readonly ?string $mime,
        public readonly int     $size,
        public readonly StreamInterface  $source,
        public readonly ?int    $error = null,
        public readonly ?string $extension = null,
        public readonly bool   $moved = false,
    ) {}
}
