<?php

namespace Arbor\http;

use Arbor\http\Request;
use Arbor\http\ServerRequest;
use BadMethodCallException;

/**
 * RequestContext class provides a wrapper around HTTP requests.
 * 
 * This class enables proxying method calls to the underlying Request object
 * while providing additional context-specific functionality.
 * 
 * @package Arbor\http\context
 * 
 */
final class RequestContext
{
    /**
     * Creates a new RequestContext instance.
     *
     * @param Request|ServerRequest $request The HTTP request object
     * @param mixed $route The route information associated with this request
     * @param bool $isErrorRequest Whether this request is an error request
     */
    public function __construct(
        protected readonly Request|ServerRequest $request,
        protected readonly mixed $route = null,
        protected readonly bool $isErrorRequest = false,
    ) {}

    /**
     * Creates a RequestContext instance from a Request object.
     *
     * @param Request $request The HTTP request to wrap
     * @return self A new RequestContext instance
     */
    public static function from(Request $request): self
    {
        return new self(
            request: $request,
        );
    }

    /**
     * Proxies method calls to the underlying Request object.
     *
     * @param string $method The method name to call
     * @param array<int, mixed> $args The arguments to pass to the method
     * 
     * @return mixed The result of the proxied method call
     * 
     * @throws BadMethodCallException When the method does not exist on the Request object
     */
    public function __call(string $method, array $args): mixed
    {
        if (!method_exists($this->request, $method)) {
            throw new BadMethodCallException(
                sprintf('Method %s::%s does not exist.', get_class($this->request), $method)
            );
        }

        return $this->request->{$method}(...$args);
    }

    /**
     * Creates a new instance with the specified route.
     *
     * @param mixed $route The route information to associate with the request
     * @return self A new RequestContext instance with the route set
     */
    public function withRoute(mixed $route): self
    {
        return new self(
            request: $this->request,
            route: $route,
            isErrorRequest: $this->isErrorRequest
        );
    }

    /**
     * Creates a new instance marked as an error request.
     *
     * @return self A new RequestContext instance with isErrorRequest set to true
     */
    public function withError(): self
    {
        return new self(
            request: $this->request,
            route: $this->route,
            isErrorRequest: true
        );
    }

    /**
     * Returns the underlying Request object.
     *
     * @return Request The HTTP request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Returns a summary of the request information.
     *
     * @return array<string, string|null> Array containing request summary information
     */
    public function getSummary(): array
    {
        $request = $this->request;
        return [
            'classFQN'   => get_class($request),
            'method'     => method_exists($request, 'getMethod') ? $request->getMethod() : null,
            'uri'        => method_exists($request, 'getUri') ? (string)$request->getUri() : null,
        ];
    }

    /**
     * Gets the path from the request URI.
     *
     * @return string|null The request path, or null if not available
     */
    public function getPath(): ?string
    {
        return method_exists($this->request, 'getUri') ? $this->request->getUri()->getPath() : null;
    }

    /**
     * Checks if the request is an AJAX request.
     *
     * @return bool True if the request has X-Requested-With header set to XMLHttpRequest
     */
    public function isAjax(): bool
    {
        return strtolower($this->request->getHeaderLine('X-Requested-With') ?? '') === 'xmlhttprequest';
    }

    /**
     * Checks if the request expects a JSON response.
     *
     * @return bool True if the Accept header contains application/json
     */
    public function expectsJson(): bool
    {
        $accept = $this->request->getHeaderLine('Accept');
        return str_contains($accept, 'application/json');
    }

    /**
     * Checks if the request is a form submission.
     *
     * @return bool True if the request method is POST/PUT/PATCH and content type is form-urlencoded
     */
    public function isFormSubmission(): bool
    {
        return in_array($this->getMethod(), ['POST', 'PUT', 'PATCH']) &&
            str_starts_with($this->request->getHeaderLine('Content-Type') ?? '', 'application/x-www-form-urlencoded');
    }

    /**
     * Gets the route name from request attributes.
     *
     * @return string|null The route name, or null if not set
     */
    public function getRouteName(): ?string
    {
        return $this->getAttribute('route.name');
    }

    /**
     * Gets the controller action from request attributes.
     *
     * @return string|null The controller action, or null if not set
     */
    public function getControllerAction(): ?string
    {
        return $this->getAttribute('controller.action');
    }


    /**
     * Checks if the request is using HTTPS.
     *
     * @return bool True if the request scheme is HTTPS
     */
    public function isSecure(): bool
    {
        return $this->getScheme() === 'https';
    }


    /**
     * Gets the path relative to the base path.
     * 
     * @return string The relative path with leading slash
     */
    public function getRelativePath(string $basePath): string
    {
        $requestedPath = $this->request->getUri()->getPath();
        $basePath = rtrim($basePath, '/');

        // Normalize slashes
        $requestedPath = '/' . ltrim($requestedPath, '/');

        // Ensure basePath is prefix of requestedPath
        if ($basePath && str_starts_with($requestedPath, $basePath . '/')) {
            $relative = substr($requestedPath, strlen($basePath));
            return '/' . ltrim($relative, '/'); // always return path with leading slash
        }

        return $requestedPath; // fallback to full path if no match
    }

    /**
     * Gets the route information.
     *
     * @return mixed The route data
     */
    public function getRoute(): mixed
    {
        return $this->route;
    }

    /**
     * Checks if this request is an error request.
     *
     * @return bool True if this is an error request, false otherwise
     */
    public function isErrorRequest(): bool
    {
        return $this->isErrorRequest;
    }
}
