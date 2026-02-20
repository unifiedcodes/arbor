<?php

namespace Arbor\files;


use LogicException;
use Arbor\files\ingress\FileContext;
use Arbor\files\Variant;
use Arbor\facades\Storage;


final class FileRecord
{
    private function __construct(
        public readonly string $uri,
        public readonly string $mime,
        public readonly string $extension,
        public readonly int $size,
        public readonly string $hash,
        public readonly string $name,
        public array $variants = [],
    ) {}


    public static function from(
        FileContext $context,
        string $uri,
    ): self {
        if (!$context->isProved()) {
            throw new LogicException(
                'Cannot create FileRecord from unproved FileContext'
            );
        }

        return new self(
            uri: $uri,
            mime: $context->mime(),
            extension: $context->extension(),
            size: $context->size(),
            hash: $context->hash(),
            name: $context->name(),
        );
    }


    public function withVariants(array $variants): self
    {
        foreach ($variants as $key => $variant) {
            if (!is_string($key) || !$variant instanceof Variant) {
                throw new LogicException('Variants must be array<string, VariantRecord>');
            }
        }

        $clone = clone $this;
        $clone->variants = $variants;

        return $clone;
    }


    public static function hydrateFromUri(string $uri): self
    {
        $absolutePath = Storage::absolutePath($uri);

        if (!is_file($absolutePath)) {
            throw new LogicException(
                "Cannot hydrate FileRecord. File not found at '{$absolutePath}'"
            );
        }

        $size = filesize($absolutePath);
        if ($size === false) {
            throw new LogicException(
                "Unable to determine file size for '{$absolutePath}'"
            );
        }

        $mime = mime_content_type($absolutePath);
        if ($mime === false) {
            throw new LogicException(
                "Unable to determine mime type for '{$absolutePath}'"
            );
        }

        $hash = hash_file('sha256', $absolutePath);
        if ($hash === false) {
            throw new LogicException(
                "Unable to determine hash for '{$absolutePath}'"
            );
        }

        $extension = pathinfo($absolutePath, PATHINFO_EXTENSION);
        $name = pathinfo($absolutePath, PATHINFO_FILENAME);

        return new self(
            uri: $uri,
            mime: $mime,
            extension: $extension,
            size: $size,
            hash: $hash,
            name: $name,
        );
    }
}
