<?php

namespace Arbor\files\contracts;


use Arbor\files\contracts\FilePolicyInterface;
use Arbor\files\state\FileContext;


interface VariantsPolicyInterface extends FilePolicyInterface
{
    public function variants(FileContext $record): array;
    public function path(FileContext $record): string;
}
