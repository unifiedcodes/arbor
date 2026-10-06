<?php

namespace Arbor\validation;

use Arbor\facade\Facade;
use Arbor\validation\Validator;


class Validate extends Facade
{
    /**
     * Get the service accessor string used to resolve the instance from the container.
     *
     * @return string
     */
    protected static function getAccessor(): string
    {
        return Validator::class;
    }
}
