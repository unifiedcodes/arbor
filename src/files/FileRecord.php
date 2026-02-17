<?php

namespace Arbor\files;


use LogicException;
use Arbor\files\ingress\FileContext;


final class FileRecord
{
    private function __construct(
        public readonly string $uri,
        public readonly string $publicUrl,

        public readonly string $mime,
        public readonly string $extension,
        public readonly int $size,
        public readonly string $hash,
        public readonly string $name,
    ) {}


    public static function from(
        FileContext $context,
        string $uri,
        string $publicUrl,
    ): self {
        if (!$context->isProved()) {
            throw new LogicException(
                'Cannot create FileRecord from unproved FileContext'
            );
        }

        return new self(
            uri: $uri,
            publicUrl: $publicUrl,

            mime: $context->mime(),
            extension: $context->extension(),
            size: $context->size(),
            hash: $context->hash(),
            name: $context->name(),
        );
    }
}
