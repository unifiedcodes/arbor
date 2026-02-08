<?php

namespace Arbor\files;


final class FileRecord
{
    private function __construct(
        public readonly string $store,
        public readonly string $path,
        public readonly string $url,

        public readonly string $mime,
        public readonly string $extension,
        public readonly int $size,
        public readonly string $hash,

        public readonly string $name,
        public readonly string $namespace,
    ) {}

    public static function from(
        FileContext $context,
        string $path,
        string $storeKey,
        string $url,
        string $namespace,
    ): self {
        return new self(
            store: $storeKey,
            path: $path,
            url: $url,

            mime: $context->mime(),
            extension: $context->extension(),
            size: $context->size(),
            hash: $context->hash(),

            name: $context->name(),
            namespace: $namespace,
        );
    }
}
