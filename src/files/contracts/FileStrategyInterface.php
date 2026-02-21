<?php

namespace Arbor\files\contracts;


use Arbor\files\state\FileContext;


interface FileStrategyInterface
{
    public function prove(FileContext $context): FileContext;
}
