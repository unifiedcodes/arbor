<?php

namespace Arbor\files\utilities;


use Arbor\files\contracts\FilePolicyInterface;
use Arbor\support\Configuration;


abstract class AbstractFilePolicy implements FilePolicyInterface
{
    use Configuration;

    public function __construct(array $options = [])
    {
        $this->applyDefaults($options);
    }


    public function withOptions(array $options = []): static
    {
        $clone = clone $this;

        $clone->options = $clone->mergeDefaults($options);

        return $clone;
    }
}
