<?php

namespace Arbor\files\contracts;

use Arbor\files\ingress\FileContext;

interface VariantProfileInterface
{
    public function name(): string;

    public function filters(FileContext $context): array;

    public function transformers(FileContext $context): array;

    public function path(): string;
}
