<?php

namespace Arbor\storage;


final class Stats
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $extension,
        public readonly string $path,
        public readonly string $type,
        public readonly int $size,
        public readonly ?string $mime,
        public readonly int $modified,
        public readonly int $created,
        public readonly int $accessed,
        public readonly string $permissions,
        public readonly ?int $inode,
        public readonly bool $binary,
    ) {}
}
