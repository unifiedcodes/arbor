<?php

namespace Arbor\router;

use Arbor\http\RequestContext;
use Arbor\http\Response;
use Arbor\pipeline\PipelineFactory;
use Arbor\router\RouteContext;
use Arbor\config\ConfigValue;

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
     * Dispatcher constructor.
     *
     * @param Router          $router          The router instance responsible for route resolution.
     * @param PipelineFactory $pipelineFactory The factory for creating pipeline instances.
     */
    public function __construct(
        protected PipelineFactory $pipelineFactory,

        #[ConfigValue('root.is_debug')]
        protected ?bool $isDebug = false,
    ) {
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
     * @return Response
     */
    public function dispatch(RouteContext $routeContext, RequestContext $request): Response
    {
        $pipeline = $this->pipelineFactory->create();

        return $pipeline->send($request)
            ->through(
                $routeContext->middlewares()
            )
            ->then(
                $routeContext->handler(),
                $routeContext->parameters()
            );
    }
}
