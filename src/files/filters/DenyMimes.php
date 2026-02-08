<?php

namespace Arbor\files\filters;


use Arbor\files\FileContext;
use Arbor\files\filters\FileFilterInterface;


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
