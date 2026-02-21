<?php

namespace Arbor\files\contracts;


use Arbor\files\contracts\FilePolicyInterface;
use Arbor\files\state\FileContext;


interface IngressPolicyInterface extends FilePolicyInterface
{
    public function strategy(FileContext $context): FileStrategyInterface;

    public function filters(FileContext $context): array;

    public function transformers(FileContext $context): array;

    public function path(FileContext $context): string;
}
