<?php

namespace Arbor\fragment;

use Arbor\container\Container;
use Arbor\router\Router;
use Arbor\http\Response;
use Arbor\http\HttpSubKernel;
use Arbor\http\traits\ResponseNormalizerTrait;
use InvalidArgumentException;

/**
 * Fragment renderer for inline controller or route fragments.
 *
 * Allows rendering a sub-request either by route name or by directly
 * invoking a controller method, and normalizes the result into a Response.
 * 
 * @package Arbor\fragment
 * 
 */
class Fragment
{
    use ResponseNormalizerTrait;

    protected Container $container;
    protected Router $router;
    protected HttpSubKernel $httpSubKernel;
    protected ?Response $lastResponse = null;

    /**
     * @param Container    $container      The DI container
     * @param Router       $router         The router for URL generation
     * @param HttpSubKernel $httpSubKernel Sub-kernel for internal sub-requests
     */
    public function __construct(Container $container, Router $router, HttpSubKernel $httpSubKernel)
    {
        $this->container     = $container;
        $this->router        = $router;
        $this->httpSubKernel = $httpSubKernel;
    }

    /**
     * Render a fragment, either by controller callable or named route.
     *
     * @param string|array $identifier  Controller callable as [Class::class, 'method'] or route name
     * @param array        $parameters  Parameters to pass to controller or route
     * @param string       $method      HTTP method when using a route
     *
     * @return Response   The fragment’s HTTP response
     *
     * @throws InvalidArgumentException If the identifier is not a valid route or callable
     */
    public function render(string|array $identifier, array $parameters = [], string $method = 'GET'): Response
    {
        if (is_array($identifier) && count($identifier) === 2) {
            /** @var class-string $class */
            [$class, $methodName] = $identifier;
            return $this->lastResponse = $this->fromController($class, $methodName, $parameters);
        }

        if (is_string($identifier)) {
            return $this->lastResponse = $this->fromRoute($identifier, $method, $parameters);
        }

        throw new InvalidArgumentException(
            'Invalid fragment identifier; expected a route name or a [Class::class, "method"] array.'
        );
    }

    /**
     * Render a fragment by named route.
     *
     * @param string $routeName  The route name to generate URL
     * @param string $method     HTTP method to use for the sub-request
     * @param array  $parameters Query or body parameters (passed via attributes)
     *
     * @return Response The fragment’s HTTP response
     */
    public function fromRoute(string $routeName, string $method = 'GET', array $parameters = []): Response
    {
        $url = $this->router->URL($routeName);

        $request = $this->httpSubKernel->create(
            uri: $url,
            method: $method,
            headers: [],
            body: '',
            attributes: $parameters
        );

        $response = $this->httpSubKernel->handle($request, true);

        return $this->ensureValidResponse($response);
    }

    /**
     * Render a fragment by directly invoking a controller method.
     *
     * @param string $className  The controller class to instantiate
     * @param string $methodName The method on the controller to call
     * @param array  $parameters Parameters to pass to the controller method
     *
     * @return Response The fragment’s HTTP response
     */
    public function fromController(string $className, string $methodName, array $parameters = []): Response
    {
        $controller = $this->container->make($className);

        /** @var mixed $result */
        $result = $this->container->call([$controller, $methodName], $parameters);

        return $this->ensureValidResponse($result);
    }

    /**
     * Get the last rendered Response, if any.
     *
     * @return Response|null
     */
    public function getLastResponse(): ?Response
    {
        return $this->lastResponse;
    }
}
