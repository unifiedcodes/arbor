<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\state\FileContext;
use LogicException;

final class FilenameLength implements FileFilterInterface
{
    public function __construct(
        private int $maxLength
    ) {}

    public function filter(FileContext $context)
    {
        if (mb_strlen($context->name()) > $this->maxLength) {
            throw new LogicException('File name is too long');
        }
    }
}
