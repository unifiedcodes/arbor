<?php

namespace Arbor\contracts\middleware;


use Arbor\http\Response;
use Arbor\http\context\RequestContext;

/**
 * Interface StageInterface
 *
 * Defines a contract for pipeline stage classes that process an incoming request,
 * optionally delegating further processing to the next stage in the pipeline.
 *
 * @package Arbor\contracts\pipeline
 * 
 */
interface StageInterface
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
