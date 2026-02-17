<?php

namespace Arbor\files\utilities;


use Arbor\support\Defaults;


abstract class AbstractFilePolicy
{
    use Defaults;

    public function __construct(array $options = [])
    {
        $this->applyDefaults($options);
    }

    abstract protected function defaults(): array;
}
