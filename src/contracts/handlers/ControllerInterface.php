<?php

namespace Arbor\contracts\handlers;

use Arbor\http\context\RequestContext;
use Arbor\http\Response;

/**
 * Interface ControllerInterface
 *
 * Defines a contract for controllers that handle HTTP requests and produce responses.
 *
 * @package Arbor\contracts
 */
interface ControllerInterface
{
    /**
     * Processes an incoming HTTP request and returns an HTTP response.
     *
     * @param RequestContext $input The incoming HTTP request.
     *
     * @return Response The HTTP response after processing the request.
     */
    public function process(RequestContext $input);
}
