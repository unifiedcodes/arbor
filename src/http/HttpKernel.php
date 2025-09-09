<?php

namespace Arbor\http;

use Arbor\router\Router;
use Arbor\attributes\ConfigValue;
use Arbor\pipeline\PipelineFactory;
use Arbor\contracts\handlers\MiddlewareInterface;
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
     * @param MiddlewareInterface $middleware
     */
    public function addMiddleware(MiddlewareInterface|string $middleware): void
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

        $initialLevel = ob_get_level();

        try {
            // Start output buffering in non-debug environments
            if (!$this->isDebug) {
                ob_start();
            }

            // Apply global middleware for main request only
            if (!$isSubRequest) {
                $requestContext = $this->executeGlobalMiddlewares($requestContext);
            }

            // Dispatch through router
            $response = $this->routerDispatch($requestContext);

            // Clean up output buffers
            if (!$this->isDebug) {
                while (ob_get_level() > $initialLevel) {
                    ob_end_clean();
                }
            }

            // Return the normalized response
            return $this->ensureValidResponse($response);
        } catch (Throwable $error) {

            if ($this->isDebug) {
                throw $error;
            }

            // Clean output buffer in case of errors
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }

            return $this->createErrorResponse($error);
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
     * @return RequestContext
     */
    protected function executeGlobalMiddlewares(RequestContext $requestContext): RequestContext
    {
        $pipeline = $this->pipelineFactory->create();

        /** @var RequestContext $context */
        $context = $pipeline
            ->send($requestContext)
            ->through($this->globalMiddlewareStack)
            ->then(fn($request) => $request);

        return $context;
    }

    /**
     * Dispatch the request context via the router.
     *
     * @param RequestContext $requestContext
     * @return mixed The controller return value (to be normalized to Response)
     */
    protected function routerDispatch(RequestContext $requestContext): mixed
    {
        return $this->router->dispatch($requestContext, $this->pipelineFactory);
    }
}
