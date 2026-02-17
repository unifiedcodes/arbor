<?php

namespace Arbor\files\filters;


use Arbor\files\ingress\FileContext;
use Arbor\files\filters\FileFilterInterface;


final class AllowedMimes implements FileFilterInterface
{
    public function __construct(
        private array $allowedMime
    ) {}

    public function filter(FileContext $context): bool
    {
        return in_array(
            $context->mime(),
            $this->allowedMime,
            true
        );
    }

    public function errorMessage(FileContext $context): string
    {
        return 'File MIME type is not allowed';
    }
}
