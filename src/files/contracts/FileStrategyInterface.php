<?php

namespace Arbor\files\strategies;


use Arbor\files\ingress\FileContext;


interface FileStrategyInterface
{
    public function prove(FileContext $context): FileContext;
}
