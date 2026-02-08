<?php

namespace Arbor\files\strategies;


use Arbor\files\FileContext;


interface FileStrategyInterface
{
    public function prove(FileContext $context): FileContext;
}
