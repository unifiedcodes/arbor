<?php

namespace Arbor\files\contracts;


use Arbor\files\ingress\FileContext;


interface FileFilterInterface
{
    public function errorMessage(FileContext $context): string;
    public function filter(FileContext $context): bool;
}
