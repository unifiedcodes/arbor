<?php

namespace Arbor\files\policies;


use Arbor\files\FileContext;
use Arbor\files\stores\FileStoreInterface;
use Arbor\files\recordStores\FileRecordStoreInterface;
use Arbor\files\strategies\FileStrategyInterface;


interface FilePolicyInterface
{

    public function strategy(FileContext $context): FileStrategyInterface;

    public function filters(FileContext $context): array;

    public function transformers(FileContext $context): array;

    public function store(FileContext $context): FileStoreInterface;

    public function recordStore(FileContext $context): ?FileRecordStoreInterface;

    public function namespace(): string;

    public function mimes(): array;
}
