<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\state\FileContext;
use LogicException;

final class AllowedExtensions implements FileFilterInterface
{
    public function __construct(
        private array $allowedExtensions
    ) {}

    public function filter(FileContext $context)
    {
        if (in_array(
            strtolower($context->extension()),
            $this->allowedExtensions,
            true
        )) {
            throw new LogicException('File extension is not allowed');
        }
    }
}
