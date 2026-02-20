<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\ingress\FileContext;
use LogicException;

final class MinFileSize implements FileFilterInterface
{
    public function __construct(
        private int $minBytes
    ) {}

    public function filter(FileContext $context)
    {
        if ($context->size() < $this->minBytes) {
            throw new LogicException('File size is too small');
        }
    }
}
