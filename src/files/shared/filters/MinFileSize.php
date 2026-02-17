<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\ingress\FileContext;


final class MinFileSize implements FileFilterInterface
{
    public function __construct(
        private int $minBytes
    ) {}

    public function filter(FileContext $context): bool
    {
        return $context->size() >= $this->minBytes;
    }

    public function errorMessage(FileContext $context): string
    {
        return 'File size is too small';
    }
}
