<?php

namespace Arbor\files\contracts;


use Arbor\files\ingress\FileContext;
use Arbor\files\contracts\FileStrategyInterface;


interface FilePolicyInterface
{
    public function strategy(FileContext $context): FileStrategyInterface;

    public function filters(FileContext $context): array;

    public function transformers(FileContext $context): array;

    public function scheme(): string;

    public function path(FileContext $context): string;

    public function mimes(): array;

    public function withOptions(array $options): static;
}
