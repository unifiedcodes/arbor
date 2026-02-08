<?php

namespace Arbor\files\filters;


use Arbor\files\FileContext;
use Arbor\files\filters\FileFilterInterface;


final class TextOnlyFilter implements FileFilterInterface
{
    public function filter(FileContext $context): bool
    {
        return !$context->isBinary();
    }

    public function errorMessage(FileContext $context): string
    {
        return 'Binary files are not allowed';
    }
}
