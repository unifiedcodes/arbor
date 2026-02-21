<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\state\FileContext;
use LogicException;

final class DenyMimes implements FileFilterInterface
{
    public function __construct(
        private array $deniedMimes
    ) {}

    public function filter(FileContext $context)
    {
        if (in_array(
            $context->mime(),
            $this->deniedMimes,
            true
        )) {
            throw new LogicException('File MIME type is forbidden');
        }
    }
}
