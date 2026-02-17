<?php

namespace Arbor\files\filters;


use Arbor\files\ingress\FileContext;
use Arbor\files\filters\FileFilterInterface;


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
