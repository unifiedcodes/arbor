<?php

namespace Arbor\files;


use LogicException;
use Arbor\files\ingress\FileContext;


final class FileRecord
{
    private function __construct(
        public readonly string $store,
        public readonly string $path,
        public readonly string $uri,
        public readonly string $publicURL,

        public readonly string $mime,
        public readonly string $extension,
        public readonly int $size,
        public readonly string $hash,

        public readonly string $name,
        public readonly string $namespace,
    ) {}

    public static function from(
        FileContext $context,
        string $storeKey,
        string $path,
        string $uri,
        string $publicURL,
        string $namespace,
    ): self {
        if (!$context->isProved()) {
            throw new LogicException(
                'Cannot create FileRecord from unproved FileContext'
            );
        }

        return new self(
            store: $storeKey,
            path: $path,
            uri: $uri,
            publicURL: $publicURL,

            mime: $context->mime(),
            extension: $context->extension(),
            size: $context->size(),
            hash: $context->hash(),

            name: $context->name(),
            namespace: $namespace,
        );
    }
}
