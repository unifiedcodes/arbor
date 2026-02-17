<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\ingress\FileContext;


final class DenyMimes implements FileFilterInterface
{
    public function __construct(
        private array $deniedMimes
    ) {}

    public function filter(FileContext $context): bool
    {
        return !in_array(
            $context->mime(),
            $this->deniedMimes,
            true
        );
    }

    public function errorMessage(FileContext $context): string
    {
        return 'File MIME type is forbidden';
    }
}
