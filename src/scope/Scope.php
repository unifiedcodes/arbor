<?php

namespace Arbor\scope;

use Arbor\facade\Facade;
use Arbor\scope\Scoper;

class Scope extends Facade
{
    /**
     * Get the service accessor string used to resolve the instance from the container.
     *
     * @return string
     */
    protected static function getAccessor(): string
    {
        return Scoper::class;
    }
}
