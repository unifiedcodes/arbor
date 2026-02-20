<?php

namespace Arbor\files\contracts;


use Arbor\files\contracts\FilePolicyInterface;
use Arbor\files\FileRecord;


interface VariantsPolicyInterface extends FilePolicyInterface
{
    public function variants(FileRecord $record): array;
    public function path(FileRecord $record): string;
}
