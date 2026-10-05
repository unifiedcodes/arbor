<?php

namespace Arbor\collections;

use Arbor\facade\Facade;
use Arbor\collections\Collector;

class Collect extends Facade
{
    /**
     * Get the service accessor string used to resolve the instance from the container.
     *
     * @return string
     */
    protected static function getAccessor(): string
    {
        return Collector::class;
    }

    // by passing facade's static method capture net -> delegating to __call of Collector.
    public static function __callStatic($method, $args)
    {
        $instance = static::resolveInstance();
        return $instance->{$method}(...$args);
    }
}
