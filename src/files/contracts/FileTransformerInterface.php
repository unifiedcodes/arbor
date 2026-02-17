<?php

namespace Arbor\files\contracts;


use Arbor\files\ingress\FileContext;


interface FileTransformerInterface
{
    /**
     * Mutates or derives a file from a trusted context.
     * Must return a NEW FileContext.
     */
    public function transform(FileContext $context): FileContext;
}
