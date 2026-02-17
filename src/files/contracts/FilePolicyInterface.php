<?php

namespace Arbor\files\policies;


use Arbor\files\ingress\FileContext;
use Arbor\files\strategies\FileStrategyInterface;
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
