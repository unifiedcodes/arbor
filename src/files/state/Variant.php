<?php

namespace Arbor\files\state;


final class Variant
{
    public function __construct(
        public readonly string $name,
        public readonly string $uri,
        public readonly string $mime,
        public readonly string $extension,
        public readonly int $size,
        public readonly string $hash,
    ) {}
}
