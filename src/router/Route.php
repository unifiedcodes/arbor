<?php

namespace Arbor\router;

use Arbor\facade\Facade;
use Arbor\router\Router;

class Route extends Facade
{
    /**
     * Get the service accessor string used to resolve the instance from the container.
     *
     * @return string
     */
    protected static function getAccessor(): string
    {
        return Router::class;
    }
}
