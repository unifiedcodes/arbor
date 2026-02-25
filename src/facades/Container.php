<?php

namespace Arbor\facades;

use Arbor\facades\Facade;
use Arbor\container\ContainerInterface;
use Exception;


class Container extends Facade
{
    /**
     * Get the service accessor string used to resolve the instance from the container.
     *
     * @return string
     */
    protected static function getAccessor(): string|object
    {
        if (!static::$container instanceof ContainerInterface) {
            throw new Exception('Container is not set.');
        }

        return static::$container;
    }
}
