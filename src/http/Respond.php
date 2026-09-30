<?php

namespace Arbor\http;

use Arbor\facade\Facade;
use Arbor\http\ResponseFactory;

class Respond extends Facade
{
    /**
     * Get the service accessor string used to resolve the instance from the container.
     *
     * @return string
     */
    protected static function getAccessor(): string
    {
        return ResponseFactory::class;
    }
}
