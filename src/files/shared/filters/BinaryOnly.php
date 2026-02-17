<?php

namespace Arbor\files\filters;


use Arbor\files\ingress\FileContext;
use Arbor\files\filters\FileFilterInterface;


final class BinaryOnly implements FileFilterInterface
{
    public function filter(FileContext $context): bool
    {
        return $context->isBinary();
    }

    public function errorMessage(FileContext $context): string
    {
        return 'Only binary files are allowed';
    }
}
