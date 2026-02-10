<?php

namespace Arbor\files\policies;


use Arbor\files\ingress\FileContext;
use Arbor\files\stores\FileStoreInterface;
use Arbor\files\strategies\FileStrategyInterface;


interface FilePolicyInterface
{
    public function strategy(FileContext $context): FileStrategyInterface;

    public function filters(FileContext $context): array;

    public function transformers(FileContext $context): array;

    public function store(FileContext $context): FileStoreInterface;

    public function namespace(): string;

    public function storePath(FileContext $context): string;

    public function mimes(): array;
}
