<?php

namespace Arbor\http;

use Arbor\router\Router;
use Arbor\config\ConfigValue;
use Arbor\pipeline\PipelineFactory;
use Arbor\pipeline\StageInterface;
use Arbor\http\Response;
use Arbor\http\Request;
use Arbor\http\RequestContext;
use Arbor\http\traits\ResponseNormalizerTrait;
use Arbor\exception\ExceptionKernel;
use Arbor\facades\Scope;
use Arbor\execution\ExecutionContext;
use Arbor\execution\ExecutionType;
use Throwable;
use Exception;

/**
 * The central HTTP kernel responsible for handling HTTP and sub-requests,
 * managing the request stack, executing middleware, and dispatching routes.
 */
class HttpKernel
{
    use ResponseNormalizerTrait;

    protected array $globalMiddlewareStack = [];


    public function __construct(
        // keep requestFactory because httpSubkernel uses it to spawn sub requests.
        protected RequestFactory $requestFactory,
        protected PipelineFactory $pipelineFactory,
        protected Router $router,
        #[ConfigValue('root.is_debug')]
        protected ?bool $isDebug = false,
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
        $initialOBLevel = ob_get_level();

        // Enter execution scope (new frame)
        Scope::enter();

        try {
            // Attach execution context
            Scope::set(
                ExecutionContext::class,
                new ExecutionContext(ExecutionType::HTTP)
            );


            // Attach request context
            $requestContext = RequestContext::from($request);
            Scope::set(RequestContext::class, $requestContext);


            // Prevent infinite recursion of requests.
            $this->assertNotAlreadyDispatched($request);


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
                $this->cleanOutputBuffer($initialOBLevel);
            }

            return $response;
        }
        // handling errors
        catch (Throwable $error) {

            if (!$this->isDebug) {
                $this->cleanOutputBuffer($initialOBLevel);
            }

            return (new ExceptionKernel($this->isDebug))->handle($error);
        }
        // Always leave scope ()
        finally {
            Scope::leave();
        }
    }


    protected function cleanOutputBuffer($oblevel)
    {
        while (ob_get_level() > $oblevel) {
            ob_end_clean();
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


    private function assertNotAlreadyDispatched(Request $request): void
    {
        $signature = $this->normalizedRequestString($request);
        $depth = Scope::depth();

        // Walk all existing frames (excluding the current one)
        for ($i = 0; $i < $depth - 1; $i++) {
            $frame = Scope::getFrame($i);

            if (!$frame || !$frame->has(RequestContext::class)) {
                continue;
            }

            $existingRequest =
                $frame->get(RequestContext::class)->getRequest();

            if ($this->normalizedRequestString($existingRequest) === $signature) {
                throw new Exception(
                    'Infinite sub-request detected for route: ' . $request->getUri()
                );
            }
        }
    }


    private function normalizedRequestString(Request $request): string
    {
        // Full URI including query string
        $uri = (string) $request->getUri();

        // Normalize trailing slash
        $uri = rtrim($uri, '/');
        $uri = $uri === '' ? '/' : $uri;

        // Normalize method
        $method = strtoupper($request->getMethod());

        return $method . ' ' . $uri;
    }
}
