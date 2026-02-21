<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\state\FileContext;
use LogicException;

final class DenyExtensions implements FileFilterInterface
{
    public function __construct(
        private array $deniedExtensions
    ) {}

    public function filter(FileContext $context)
    {
        if (in_array(
            strtolower($context->extension()),
            $this->deniedExtensions,
            true
        )) {
            throw new LogicException('File extension is forbidden');
        }
    }
}
