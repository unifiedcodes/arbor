<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\ingress\FileContext;
use LogicException;

final class TextOnlyFilter implements FileFilterInterface
{
    public function filter(FileContext $context)
    {
        if ($context->isBinary()) {
            throw new LogicException('Binary files are not allowed');
        }
    }
}
