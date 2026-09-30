<?php

namespace Arbor\collection;

use Arbor\facade\Facade;
use Arbor\collection\Collector;

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
}
