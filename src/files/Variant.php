<?php

namespace Arbor\files;


final class Variant
{
    public function __construct(
        public readonly string $name,

        public readonly string $uri,
        public readonly string $publicUrl,

        public readonly string $mime,
        public readonly string $extension,
        public readonly int $size,
        public readonly string $hash,
    ) {}
}
