<?php

namespace Arbor\facades;

use Arbor\facades\Facade;


class Scope extends Facade
{
    /**
     * Get the service accessor string used to resolve the instance from the container.
     *
     * @return string
     */
    protected static function getAccessor(): string|object
    {
        return 'Arbor\\scope\\Scope';
    }
}
