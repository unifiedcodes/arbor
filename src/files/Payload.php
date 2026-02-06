<?php

namespace Arbor\files;


final class Payload
{
    public function __construct(
        public readonly string $originalName,
        public readonly string $mime,
        public readonly int    $size,
        public readonly mixed  $source,   // string path | resource | stream
        public readonly array  $meta = []  // request headers, tmp_name, etc
    ) {}
}
