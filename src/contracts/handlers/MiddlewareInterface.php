<?php

namespace Arbor\contracts\handlers;


use Arbor\http\Response;
use Arbor\http\context\RequestContext;

/**
 * Interface MiddlewareInterface
 *
 * Defines a contract for middleware classes that process an incoming HTTP request,
 * optionally delegating further processing to the next middleware in the pipeline.
 *
 * @package Arbor\contracts
 */
interface MiddlewareInterface
{
    /**
     * Processes an incoming HTTP request and returns an HTTP response.
     *
     * This method should perform any necessary operations on the request and then
     * delegate processing to the next middleware or handler using the provided callable.
     *
     * @param RequestContext  $input The incoming HTTP request.
     * @param callable $next  The next middleware or handler to process the request.
     *
     * @return Response The HTTP response resulting from processing the request.
     */
    public function process(RequestContext $input, callable $next): Response;
}
