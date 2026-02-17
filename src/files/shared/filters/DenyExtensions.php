<?php

namespace Arbor\files\filters;


use Arbor\files\ingress\FileContext;
use Arbor\files\filters\FileFilterInterface;


final class DenyExtensions implements FileFilterInterface
{
    public function __construct(
        private array $deniedExtensions
    ) {}

    public function filter(FileContext $context): bool
    {
        return !in_array(
            strtolower($context->extension()),
            $this->deniedExtensions,
            true
        );
    }

    public function errorMessage(FileContext $context): string
    {
        return 'File extension is forbidden';
    }
}
