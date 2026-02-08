<?php

namespace Arbor\files\filters;


use Arbor\files\FileContext;
use Arbor\files\filters\FileFilterInterface;


final class MaxFileSize implements FileFilterInterface
{
    public function __construct(
        private int $maxBytes
    ) {}

    public function filter(FileContext $context): bool
    {
        return $context->size() <= $this->maxBytes;
    }

    public function errorMessage(FileContext $context): string
    {
        return 'File size exceeds allowed limit';
    }
}
