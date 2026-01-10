<?php

namespace Arbor\fragment;

use Stringable;
use Arbor\router\Router;
use Arbor\http\Response;
use Arbor\http\ServerRequest;
use Arbor\http\HttpSubKernel;
use InvalidArgumentException;
use Arbor\http\components\Uri;
use Arbor\http\traits\ResponseNormalizerTrait;
use Arbor\facades\Container;

/**
 * Fragment Class
 * 
 * This class provides methods to execute sub-requests within the main application,
 * allowing for the creation of reusable fragments of content that can be composed
 * together to build complete responses.
 * 
 * Fragments can be generated from routes, controllers, URIs, or custom requests.
 * 
 */
class Fragment
{
    use ResponseNormalizerTrait;


    /**
     * Constructor.
     *
     * @param Router        $router        The router instance.
     * @param HttpSubKernel $httpSubKernel The HTTP sub-kernel.
     */
    public function __construct(
        protected Router $router,
        protected HttpSubKernel $httpSubKernel
    ) {}

    /**
     * Generate a fragment by executing a named route.
     * 
     * @param string $routeName  The name of the route to execute.
     * @param string $method     The HTTP method to use (default: 'GET').
     * @param array<string,mixed> $parameters Additional parameters to pass to the route.
     * 
     * @return Response The response from the route.
     */
    public function route(string $routeName, string $method = 'GET', array $parameters = []): Response
    {
        $url = $this->router->URL($routeName);

        $request = $this->httpSubKernel->create(
            uri: $url,
            method: $method,
            headers: [],
            body: '',
            attributes: $parameters
        );

        return $this->httpSubKernel->handle($request, true);
    }

    /**
     * Generate a fragment by executing a controller.
     * 
     * @param callable|string|array<int,string|object> $controller The controller to execute, either:
     *                                                                 - A ControllerInterface instance
     *                                                                 - An array of [ControllerClass::class, 'methodName']
     *                                                                 - A string of Controller FQN
     * @param array<string,mixed> $parameters Additional parameters to pass to the controller.
     * 
     * @return Response The response from the controller.
     * 
     * @throws InvalidArgumentException If the controller is not properly specified.
     */
    public function controller(callable|array|string $controller, array $parameters = []): Response
    {
        if (is_callable($controller)) {
            return $this->fromCallable($controller, $parameters);
        }

        if (is_string($controller)) {
            return $this->fromController($controller, 'process', $parameters);
        }

        if (is_array($controller) && count($controller) === 2) {
            [$class, $method] = $controller;
            return $this->fromController($class, $method, $parameters);
        }

        throw new InvalidArgumentException(
            'Controller must be callable, class-string, or [Class::class, "method"]'
        );
    }

    /**
     * Generate a fragment by executing a request to a specific URI.
     * 
     * @param string|Stringable|Uri $uri       The URI to request.
     * @param string                $method    The HTTP method to use (default: 'GET').
     * @param array<string,mixed>   $parameters Additional parameters to pass to the request.
     * 
     * @return Response The response from the URI.
     */
    public function uri(string|Stringable|Uri $uri, string $method = 'GET', array $parameters = []): Response
    {
        $uri = (string) $uri;

        $request = $this->httpSubKernel->create(
            uri: $uri,
            method: $method,
            headers: [],
            body: '',
            attributes: $parameters
        );

        return $this->httpSubKernel->handle($request, true);
    }

    /**
     * Generate a fragment by executing a custom server request.
     * 
     * @param ServerRequest $request The server request to execute.
     * 
     * @return Response The response from the request.
     */
    public function request(ServerRequest $request): Response
    {
        return $this->httpSubKernel->handle($request, true);
    }

    /**
     * Execute a controller method and ensure it returns a valid response.
     * 
     * @param string               $className  The fully qualified class name of the controller.
     * @param string               $methodName The method name to call on the controller.
     * @param array<string,mixed>  $parameters Additional parameters to pass to the controller method.
     * 
     * @return Response The normalized response from the controller.
     */
    protected function fromController(string $className, string $methodName, array $parameters = []): Response
    {
        $result = Container::call([$className, $methodName], $parameters);
        return $this->ensureValidResponse($result);
    }

    protected function fromCallable(callable $callable, array $parameters = []): Response
    {
        $result = Container::call($callable, $parameters);
        return $this->ensureValidResponse($result);
    }
}
