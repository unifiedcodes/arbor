<?php

namespace Arbor\files\contracts;


use Arbor\files\ingress\FileContext;
use Arbor\storage\Uri;


interface VariantInterface
{
    public function name(): string;

    public function filters(FileContext $context): array;

    public function transformers(FileContext $context): array;

    public function uri(FileContext $context): Uri;
}
