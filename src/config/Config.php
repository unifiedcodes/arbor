<?php

namespace Arbor\config;

use Arbor\facade\Facade;
use Arbor\config\Configurator;


class Config extends Facade
{
    /**
     * Get the service accessor string used to resolve the instance from the container.
     *
     * @return string
     */
    protected static function getAccessor(): string
    {
        return Configurator::class;
    }
}
