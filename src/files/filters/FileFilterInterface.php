<?php

namespace Arbor\files\filters;


use Arbor\files\FileContext;


interface FileFilterInterface
{
    public function errorMessage(FileContext $context): string;
    public function filter(FileContext $context): bool;
}
