<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\ingress\FileContext;


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
