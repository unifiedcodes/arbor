<?php

namespace Arbor\storage\namespace;

use BackedEnum;
use LogicException;


trait NamespaceTrait
{
    public function folder(): string
    {
        if (! $this instanceof BackedEnum) {
            throw new LogicException(
                static::class . ' must be a backed enum.'
            );
        }

        return (string) $this->value;
    }
}
