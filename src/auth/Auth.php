<?php

namespace Arbor\auth;

use Arbor\facade\Facade;
use Arbor\auth\Authenticator;


class Auth extends Facade
{
    /**
     * Get the service accessor string used to resolve the instance from the container.
     *
     * @return string
     */
    protected static function getAccessor(): string
    {
        return Authenticator::class;
    }
}
