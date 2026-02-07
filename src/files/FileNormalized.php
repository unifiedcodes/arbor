<?php

namespace Arbor\files;


final class FileNormalized
{
    public function __construct(
        public readonly string $path,
        public readonly string $mime,
        public readonly string $extension
    ) {}
}
