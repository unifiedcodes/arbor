<?php

namespace Arbor\files\contracts;

use Arbor\files\state\FileContext;

interface VariantProfileInterface
{
    public function name(): string;

    public function filters(FileContext $context): array;

    public function transformers(FileContext $context): array;

    public function path(): string;
}
