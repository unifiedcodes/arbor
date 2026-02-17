<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\ingress\FileContext;


final class AllowedExtensions implements FileFilterInterface
{
    public function __construct(
        private array $allowedExtensions
    ) {}

    public function filter(FileContext $context): bool
    {
        return in_array(
            strtolower($context->extension()),
            $this->allowedExtensions,
            true
        );
    }

    public function errorMessage(FileContext $context): string
    {
        return 'File extension is not allowed';
    }
}
