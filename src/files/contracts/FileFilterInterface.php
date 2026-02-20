<?php

namespace Arbor\files\contracts;


use Arbor\files\ingress\FileContext;


interface FileFilterInterface
{
    public function filter(FileContext $context);
}
