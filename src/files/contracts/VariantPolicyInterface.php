<?php

namespace Arbor\files\contracts;


use Arbor\files\contracts\FilePolicyInterface;
use Arbor\files\FileRecord;


interface VariantPolicyInterface extends FilePolicyInterface
{
    public function filters(FileRecord $fileRecord): array;

    public function transformers(FileRecord $fileRecord): array;
}
