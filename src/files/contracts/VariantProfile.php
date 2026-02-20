<?php

namespace Arbor\files\contracts;

use Arbor\files\FileRecord;


interface VariantInterface
{
    public function name(): string;

    public function filters(FileRecord $record): array;

    public function transformers(FileRecord $record): array;

    public function path(): string;
}
