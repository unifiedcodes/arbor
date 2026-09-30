<?php

namespace Arbor\storage;

use Arbor\facade\Facade;
use Arbor\storage\Storage;


class Store extends Facade
{
    /**
     * Get the service accessor string used to resolve the instance from the container.
     *
     * @return string
     */
    protected static function getAccessor(): string
    {
        return Storage::class;
    }
}
