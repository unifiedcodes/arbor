<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\ingress\FileContext;


final class FilenameLength implements FileFilterInterface
{
    public function __construct(
        private int $maxLength
    ) {}

    public function filter(FileContext $context): bool
    {
        return mb_strlen($context->originalName()) <= $this->maxLength;
    }

    public function errorMessage(FileContext $context): string
    {
        return 'File name is too long';
    }
}
