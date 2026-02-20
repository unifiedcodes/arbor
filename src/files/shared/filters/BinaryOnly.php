<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\ingress\FileContext;
use LogicException;

final class BinaryOnly implements FileFilterInterface
{
    public function filter(FileContext $context)
    {
        if (! $context->isBinary()) {
            throw new LogicException('Only binary files are allowed');
        }
    }
}
