<?php

namespace Arbor\files;


use LogicException;
use Arbor\files\ingress\FileContext;
use Arbor\files\Variant;


final class FileRecord
{
    private function __construct(
        public readonly string $uri,
        public readonly string $mime,
        public readonly string $extension,
        public readonly int $size,
        public readonly string $name,
        public readonly bool $binary,
        public readonly ?string $hash = null,
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
            binary: $context->isBinary()
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
}
