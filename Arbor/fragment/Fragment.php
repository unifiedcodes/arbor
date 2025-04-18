<?php

namespace Arbor\fragment;


use Arbor\container\Container;
use Arbor\router\Router;
use Arbor\http\Response;
use Arbor\http\HttpSubKernel;
use Arbor\http\traits\ResponseNormalizerTrait;

use InvalidArgumentException;

class Fragment
{
    use ResponseNormalizerTrait;


    protected Container $container;
    protected Router $router;
    protected HttpSubKernel $httpSubKernel;
    protected $lastResponse;


    public function __construct(Container $container, Router $router, HttpSubKernel $httpSubKernel)
    {
        $this->container = $container;
        $this->router = $router;
        $this->httpSubKernel = $httpSubKernel;
    }


    public function render(string|array $identifier, array $parameters = [], string $method = 'GET'): Response
    {
        // Case: [ControllerClass::class, 'method']
        if (is_array($identifier) && count($identifier) === 2) {
            [$class, $methodName] = $identifier;
            return $this->lastResponse = $this->fromController($class, $methodName, $parameters);
        }

        // Case: Named route
        if (is_string($identifier)) {
            return $this->lastResponse = $this->fromRoute($identifier, $method, $parameters);
        }

        throw new InvalidArgumentException("Invalid fragment identifier, expects a valid Route Name or Callable Array");
    }


    public function fromRoute($routeName, $method, $parameters): Response
    {
        // build url from router.
        $url = $this->router->URL($routeName);

        // build request.
        $request = $this->httpSubKernel->create($url, $method);

        // dispatch with httpSubKernel
        $response = $this->httpSubKernel->handle($request, true);

        return $this->ensureValidResponse($response);
    }


    public function fromController($className, $methodName, $parameters = []): Response
    {
        // use DI to invoke controller.
        $class = $this->container->make($className);

        // use DI to call a dedicated method process in our case..
        $response = $this->container->call([$class, $methodName], $parameters);

        return $this->ensureValidResponse($response);
    }
}
