<?php

namespace Arbor\view;

use Arbor\facade\Facade;
use Arbor\view\Presenter;


class View extends Facade
{
    /**
     * Get the service accessor string used to resolve the instance from the container.
     *
     * @return string
     */
    protected static function getAccessor(): string
    {
        return Presenter::class;
    }
}
