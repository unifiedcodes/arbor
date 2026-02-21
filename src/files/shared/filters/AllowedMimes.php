<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\state\FileContext;
use LogicException;

final class AllowedMimes implements FileFilterInterface
{
    public function __construct(
        private array $allowedMime
    ) {}

    public function filter(FileContext $context)
    {
        if (! in_array(
            $context->mime(),
            $this->allowedMime,
            true
        )) {
            throw new LogicException('File MIME type is not allowed');
        }
    }
}
