<?php

namespace Arbor\files\contracts;


use Arbor\files\state\FileContext;


interface FileFilterInterface
{
    public function filter(FileContext $context);
}
