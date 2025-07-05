<?php

namespace Arbor\router;

use Arbor\http\context\RequestContext;
use Arbor\pipeline\PipelineFactory;

/**
 * Class Dispatcher
 *
 * Handles the incoming HTTP request by resolving the appropriate route
 * using the Router and then processing the request through the pipeline.
 *
 * @package Arbor\router
 * 
 */
class Dispatcher
{
    /**
     * The pipeline factory instance used to create pipelines for request processing.
     *
     * @var PipelineFactory
     * 
     */
    protected PipelineFactory $pipelineFactory;

    /**
     * Dispatcher constructor.
     *
     * @param Router          $router          The router instance responsible for route resolution.
     * @param PipelineFactory $pipelineFactory The factory for creating pipeline instances.
     */
    public function __construct(PipelineFactory $pipelineFactory)
    {
        $this->pipelineFactory = $pipelineFactory;
    }

    /**
     * Handles the incoming request.
     *
     * Resolves the route using the Router and prints the match information.
     * The method is designed to eventually run the pipeline for further processing,
     * as indicated by the commented code.
     * 
     * @param array $foundmatch The match found from router. by router->resolve($request) method
     * @param RequestContext $request The incoming HTTP request.
     *
     * @return mixed
     */
    public function dispatch(array $foundMatch, RequestContext $request): mixed
    {
        ['handler' => $controller, 'middlewares' => $middlewares, 'parameters' => $parameters] = $foundMatch;

        $pipeline = $this->pipelineFactory;

        return $pipeline()->send($request)
            ->through($middlewares)
            ->then($controller);
    }
}
