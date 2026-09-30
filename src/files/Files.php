<?php

namespace Arbor\files;

use Arbor\facade\Facade;
use Arbor\files\FileManager;

class Files extends Facade
{
    /**
     * Get the service accessor string used to resolve the instance from the container.
     *
     * @return string
     */
    protected static function getAccessor(): string
    {
        return FileManager::class;
    }
}
