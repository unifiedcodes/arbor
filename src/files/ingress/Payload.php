<?php

namespace Arbor\files\ingress;


final class Payload
{
    public function __construct(
        public readonly string  $name,
        public readonly ?string $mime,
        public readonly int     $size,
        public readonly mixed   $source,
        public readonly ?int    $error = null,
        public readonly ?string $extension = null,
        public readonly bool   $moved = false,
    ) {}
}
