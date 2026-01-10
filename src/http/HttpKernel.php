<?php

namespace Arbor\http;

use Arbor\router\Router;
use Arbor\attributes\ConfigValue;
use Arbor\pipeline\PipelineFactory;
use Arbor\contracts\middleware\StageInterface;
use Arbor\http\Response;
use Arbor\http\Request;
use Arbor\http\context\RequestContext;
use Arbor\http\context\RequestStack;
use Arbor\http\traits\ResponseNormalizerTrait;
use Throwable;
use Exception;

/**
 * The central HTTP kernel responsible for handling HTTP and sub-requests,
 * managing the request stack, executing middleware, and dispatching routes.
 */
class HttpKernel
{
    use ResponseNormalizerTrait;

    protected RequestFactory $requestFactory;
    protected PipelineFactory $pipelineFactory;
    protected Router $router;
    protected RequestStack $requestStack;
    protected bool $isDebug = false;
    protected array $globalMiddlewareStack = [];

    /**
     * @param RequestFactory $requestFactory for creating requests (will be inherited by HTTPSubKernel to make sub requests)
     * @param RequestStack $requestStack Stack managing request contexts
     * @param PipelineFactory $pipelineFactory Factory to create middleware pipelines
     * @param Router $router The router instance
     * @param bool|null $isDebug Enables debugging mode if true
     */
    public function __construct(
        RequestFactory $requestFactory,
        RequestStack $requestStack,
        PipelineFactory $pipelineFactory,
        Router $router,
        #[ConfigValue('root.is_debug')]
        ?bool $isDebug = false,
    ) {
        $this->requestFactory = $requestFactory;
        $this->requestStack = $requestStack;
        $this->pipelineFactory = $pipelineFactory;
        $this->router = $router;

        $this->isDebug = $isDebug ?: false;
    }


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
        $this->globalMiddlewareStack[] = $middleware;
    }

    /**
     * Handles an incoming request, manages its context and routing, and returns a response.
     * Also applies global middlewares to non-subrequests and catches errors to ensure a valid response.
     *
     * @param Request $request The incoming request
     * @param bool $isSubRequest True if this is an internal sub-request
     * @return Response The processed response
     */
    public function handle(Request $request, bool $isSubRequest = false): Response
    {
        $requestContext = new RequestContext($request);

        // Push context into the stack
        $this->requestStack->push($requestContext);

        // Prevent infinite recursion for sub-requests
        if ($this->requestStack->alreadyDispatched($request)) {
            throw new Exception("Infinite sub-request detected for route: " . $request->getUri());
        }

        $initialOBLevel = ob_get_level();

        try {
            // Start output buffering in non-debug environments
            if (!$this->isDebug) {
                ob_start();
            }

            if ($isSubRequest) {
                // Dispatch through router directly
                $response = $this->routerDispatch($requestContext);
            } else {
                // Apply global middleware for main request only and dispatch.
                $response = $this->executeGlobalMiddlewares($requestContext);
            }

            // Clean up output buffers
            if (!$this->isDebug) {
                while (ob_get_level() > $initialOBLevel) {
                    ob_end_clean();
                }
            }

            return $response;
        } catch (Throwable $error) {

            return $this->handleException($error, $requestContext, $initialOBLevel);
        } finally {
            // Sub-requests should be removed from stack after handling
            if ($isSubRequest === true) {
                $this->requestStack->pop();
            }
        }
    }

    /**
     * Execute the global middleware pipeline on the request context.
     *
     * @param RequestContext $requestContext
     * @return Response
     */
    protected function executeGlobalMiddlewares(RequestContext $requestContext): Response
    {
        $pipeline = $this->pipelineFactory->create();

        return $pipeline
            ->send($requestContext)
            ->through($this->globalMiddlewareStack)
            ->then(function (RequestContext $input) {
                return $this->routerDispatch($input);
            });
    }

    /**
     * Dispatch the request context via the router.
     *
     * @param RequestContext $requestContext
     * @return Response The controller return value (to be normalized to Response)
     */
    protected function routerDispatch(RequestContext $requestContext): Response
    {
        return $this->router->dispatch($requestContext);
    }


    protected function handleException($error, $requestContext, $initialOBLevel)
    {
        if ($this->isDebug) {
            throw $error;
        }

        // Clean output buffer in case of errors
        while (ob_get_level() > $initialOBLevel) {
            ob_end_clean();
        }

        // recursion guard
        if ($requestContext->isErrorRequest()) {
            return $this->createErrorResponse(
                new Exception('Internal Server Error', 500)
            );
        }

        // marking request is already handling Error.
        $requestContext->markErrorRequest();

        // dispatching error page or outputting a response.
        $routeContext = $this->router->resolveErrorPage($error, $requestContext);

        if ($routeContext) {
            return $this->router->dispatchRoute(
                $routeContext,
                $requestContext
            );
        }
        // returning with a vague error response in production.
        else {
            return $this->createErrorResponse(
                new Exception('Internal Server Error', 500)
            );
        }
    }
}
