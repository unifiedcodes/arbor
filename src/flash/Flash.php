<?php

namespace Arbor\flash;

use Arbor\facade\Facade;
use Arbor\flash\Flasher;

class Flash extends Facade
{
    /**
     * Get the service accessor string used to resolve the instance from the container.
     *
     * @return string
     */
    protected static function getAccessor(): string
    {
        return Flasher::class;
    }
}
