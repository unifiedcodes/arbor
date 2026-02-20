<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\ingress\FileContext;
use LogicException;

final class MaxFileSize implements FileFilterInterface
{
    public function __construct(
        private int $maxBytes
    ) {}

    public function filter(FileContext $context)
    {
        if ($context->size() > $this->maxBytes) {
            throw new LogicException('File size exceeds allowed limit');
        }
    }
}
