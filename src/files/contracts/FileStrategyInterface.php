<?php

namespace Arbor\files\contracts;


use Arbor\files\ingress\FileContext;


interface FileStrategyInterface
{
    public function prove(FileContext $context): FileContext;
}
