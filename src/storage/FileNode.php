<?php

namespace Arbor\storage;


use DateTimeInterface;


class FileNode
{
    public function __construct(
        public string $name,
        public string $path,
        public bool   $isDirectory,

        public ?int   $size = null,
        public ?string $mimeType = null,

        public ?DateTimeInterface $createdAt = null,
        public ?DateTimeInterface $modifiedAt = null,

        public ?array $meta = null
    ) {}
}
