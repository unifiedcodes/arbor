<?php

namespace Arbor\fragment;

use Stringable;
use Arbor\router\Router;
use Arbor\http\Response;
use Arbor\http\ServerRequest;
use Arbor\http\HttpSubKernel;
use InvalidArgumentException;
use Arbor\http\components\Uri;
use Arbor\container\ServiceContainer;
use Arbor\contracts\handlers\ControllerInterface;
use Arbor\http\traits\ResponseNormalizerTrait;

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
     * The dependency injection container.
     *
     * @var ServiceContainer
     */
    protected ServiceContainer $container;

    /**
     * The router instance.
     *
     * @var Router
     */
    protected Router $router;

    /**
     * The HTTP sub-kernel for handling sub-requests.
     *
     * @var HttpSubKernel
     */
    protected HttpSubKernel $httpSubKernel;

    /**
     * Constructor.
     *
     * @param ServiceContainer     $container     The dependency injection container.
     * @param Router        $router        The router instance.
     * @param HttpSubKernel $httpSubKernel The HTTP sub-kernel.
     */
    public function __construct(ServiceContainer $container, Router $router, HttpSubKernel $httpSubKernel)
    {
        $this->container     = $container;
        $this->router        = $router;
        $this->httpSubKernel = $httpSubKernel;
    }

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

        return $this->ensureValidResponse($this->httpSubKernel->handle($request, true));
    }

    /**
     * Generate a fragment by executing a controller.
     * 
     * @param ControllerInterface|string|array<int,string|object> $controller The controller to execute, either:
     *                                                                 - A ControllerInterface instance
     *                                                                 - An array of [ControllerClass::class, 'methodName']
     *                                                                 - A string of Controller FQN
     * @param array<string,mixed> $parameters Additional parameters to pass to the controller.
     * 
     * @return Response The response from the controller.
     * 
     * @throws InvalidArgumentException If the controller is not properly specified.
     */
    public function controller(ControllerInterface|array|string $controller, array $parameters = []): Response
    {
        if (is_string($controller)) {
            return $this->fromController($controller, 'process', $parameters);
        }

        if ($controller instanceof ControllerInterface) {
            return $this->fromController($controller::class, 'process', $parameters);
        }

        if (is_array($controller) && count($controller) === 2) {
            [$class, $method] = $controller;
            return $this->fromController($class, $method, $parameters);
        }

        throw new InvalidArgumentException('Controller must be a valid instance or an instance of ControllerInterface or [Class::class, "method"] array.');
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

        return $this->ensureValidResponse($this->httpSubKernel->handle($request, true));
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
        return $this->ensureValidResponse($this->httpSubKernel->handle($request, true));
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
        $controller = $this->container->make($className, $parameters);
        $result = $this->container->call([$controller, $methodName]);

        return $this->ensureValidResponse($result);
    }
}
