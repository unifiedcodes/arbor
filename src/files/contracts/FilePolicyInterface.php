<?php

namespace Arbor\files\contracts;


use Arbor\files\ingress\FileContext;
use Arbor\files\contracts\FileStrategyInterface;
use Arbor\storage\Uri;


interface FilePolicyInterface
{
    public function strategy(FileContext $context): FileStrategyInterface;

    public function filters(FileContext $context): array;

    public function transformers(FileContext $context): array;

    public function uri(FileContext $context): Uri;

    public function namespace(): string;

    public function mimes(): array;

    public function variations(): array;
}
