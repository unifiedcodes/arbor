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


class HttpKernel
{

    use ResponseNormalizerTrait;


    protected PipelineFactory $pipelineFactory;
    protected Router $router;
    protected RequestStack $requestStack;
    protected RequestFactory $requestFactory;
    protected bool $isDebug = false;
    protected array $globalMiddlewareStack = [];
    protected string $baseURI = '';



    public function __construct(
        RequestFactory $requestFactory,
        RequestStack $requestStack,
        PipelineFactory $pipelineFactory,
        Router $router,
        #[ConfigValue('app.isDebug')]
        ?bool $isDebug = false,
        #[ConfigValue('app.baseURI')]
        string $baseURI = ''
    ) {
        // dependencies.
        $this->requestFactory = $requestFactory;
        $this->requestStack = $requestStack;
        $this->pipelineFactory = $pipelineFactory;
        $this->router = $router;

        // configs
        $this->isDebug = $isDebug ?: false;
        $this->baseURI = $baseURI ?: '';
    }



    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->globalMiddlewareStack[] = $middleware;
    }


    /*
    * only this method is responsible for turning responses into valid Response object.
    * only this method is responsible for wrapping raw requests into valid requestContext
    * and pushing it in request stack.
    */
    public function handle(Request $request, bool $isSubRequest = false): Response
    {
        // prepare context.
        $requestContext = new RequestContext($request, $this->baseURI);

        // if request is subrequest ----> do inherit from parent & main here.

        // push to stack.
        $this->requestStack->push($requestContext);

        // checking for circular calls.
        if ($this->requestStack->alreadyDispatched($request)) {
            throw new Exception("Infinite sub-request detected for route: " . $request->getUri());
        }

        // calculate outputbuffer levels.
        $initialLevel = ob_get_level();

        try {

            // start output buffer if not debug environment.
            if (!$this->isDebug) {
                ob_start();
            }

            if (!$isSubRequest) {
                // if not subrequest run globalmiddlewares and recieves modified requestContext.
                $requestContext = $this->executeGlobalMiddlewares($requestContext);
            }

            // capture response.
            $response = $this->routerDispatch($requestContext);


            // cleaning up output buffer.
            if (!$this->isDebug) {
                while (ob_get_level() > $initialLevel) {
                    ob_end_clean();
                }
            }

            // returning formatted response object.
            return $this->ensureValidResponse($response);
        }

        // catch error and decide to rethrow error or create a valid response (default)
        catch (Throwable $error) {

            // clean output buffer.
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }

            return $this->createErrorResponse($error);
        }

        // pop out request from stack if it's sub request.
        finally {
            if ($isSubRequest === true) {
                $this->requestStack->pop();
            }
        }
    }



    protected function executeGlobalMiddlewares(RequestContext $requestContext)
    {
        $pipeline = $this->pipelineFactory->create();

        return $pipeline
            ->send($requestContext)
            ->through($this->globalMiddlewareStack)
            ->then(fn($request) => $request);
    }



    protected function routerDispatch(RequestContext $requestContext): mixed
    {
        // Delegate the dispatching to the router and get the response
        return $this->router->dispatch($requestContext, $this->pipelineFactory);
    }
}
