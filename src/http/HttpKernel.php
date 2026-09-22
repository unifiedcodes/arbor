<?php

namespace Arbor\http;

use Arbor\facades\Route;
use Arbor\pipeline\Pipeline;
use Arbor\pipeline\StageInterface;
use Arbor\http\Response;
use Arbor\http\Request;
use Arbor\http\RequestContext;
use Exception;

/**
 * The central HTTP kernel responsible for handling HTTP and sub-requests,
 * managing the request stack, executing middleware, and dispatching routes.
 */
class HttpKernel
{
    protected array $middlewares = [];

    public function __construct(
        protected Pipeline $pipeline,
    ) {}


    public function useMiddlewares(array $middlewares)
    {
        foreach ($middlewares as $middleware) {
            $this->addMiddleware($middleware);
        }
    }

    /**
     * Add a global middleware to be executed for every request.
     *
     * @param StageInterface $middleware
     */
    public function addMiddleware(StageInterface|string $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Handles an incoming request, manages its context and routing, and returns a response.
     * Also applies global middlewares to non-subrequests and catches errors to ensure a valid response.
     *
     * @param Request $request The incoming request
     * @param bool $isSubRequest True if this is an internal sub-request
     * @return Response The processed response
     * 
     */
    public function handle(RequestContext $requestContext): Response
    {
        // Apply global middleware for main request only and dispatch.
        return $this->pipeline
            ->send($requestContext)
            ->through($this->middlewares)
            ->then(function () use ($requestContext) {
                return $this->routeDispatch($requestContext);
            });
    }

    public function routeDispatch(RequestContext $requestContext): Response
    {
        // get routecontext from router.
        $routeContext = Route::resolve(
            $requestContext->getRequestPath(),
            $requestContext->getMethod()
        );

        return Route::dispatch($routeContext);
    }
}
