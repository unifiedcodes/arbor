<?php

namespace Arbor\container;

use Exception;
use Arbor\facade\Facade;
use Arbor\container\ContainerInterface;


class Container extends Facade
{
    protected static function getAccessor(): string
    {
        throw new LogicException(
            'Container facade does not use a service accessor.'
        );
    }

    protected static function resolveInstance(): object
    {
        if (!static::$container instanceof ContainerInterface) {
            throw new Exception('Container is not set.');
        }

        return static::$container;
    }
}
