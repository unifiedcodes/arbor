<?php

namespace Arbor\http\context;

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
 * 
 * Dynamically call to Request by following methods.
 * 
 * @method string getMethod()
 * 
 */
class RequestContext
{
    /**
     * The underlying HTTP request object.
     */
    protected Request|ServerRequest $request;
    protected string $baseURI = '';
    protected string $basePath = '';

    /**
     * Creates a new RequestContext instance.
     *
     * @param Request $request The HTTP request to wrap
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

        if ($request instanceof ServerRequest) {
            $this->setBaseURI($this->request->getBaseURI());
        }
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
     * Proxies method calls to the underlying Request object.
     *
     * @param string $method The method name to call
     * @param array<int, mixed> $args The arguments to pass to the method
     * 
     * @return mixed The result of the proxied method call
     * 
     * @throws BadMethodCallException When the method does not exist on the Request object
     * 
     */
    public function __call(string $method, array $args): mixed
    {
        if (method_exists($this->request, $method)) {
            return $this->request->{$method}(...$args);
        }

        throw new BadMethodCallException("Method {$method} not found on request.");
    }


    // accessors
    public function getPath(): ?string
    {
        return method_exists($this->request, 'getUri') ? $this->request->getUri()->getPath() : null;
    }

    public function isAjax(): bool
    {
        return strtolower($this->getHeader('X-Requested-With') ?? '') === 'xmlhttprequest';
    }

    public function expectsJson(): bool
    {
        $accept = $this->getHeader('Accept');
        return str_contains($accept, 'application/json');
    }

    public function isFormSubmission(): bool
    {
        return in_array($this->getMethod(), ['POST', 'PUT', 'PATCH']) &&
            str_starts_with($this->getHeader('Content-Type') ?? '', 'application/x-www-form-urlencoded');
    }

    // if router binds data.
    public function getRouteName(): ?string
    {
        return $this->getAttribute('route.name');
    }

    public function getControllerAction(): ?string
    {
        return $this->getAttribute('controller.action');
    }

    public function getFullUrl(): string
    {
        return $this->getScheme() . '://' . $this->getHost() . $this->getBaseUrl() . $this->getPathInfo() .
            ($this->getQueryString() ? '?' . $this->getQueryString() : '');
    }

    public function isSecure(): bool
    {
        return $this->getScheme() === 'https';
    }


    protected static function prepareBaseURI($baseURI)
    {
        $parseURI = parse_url($baseURI ?? '');

        if (!isset($parseURI['scheme'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $baseURI = $scheme . $baseURI;
        }

        return $baseURI;
    }

    /**
     * Sets the base URI and extracts the base path
     * 
     * @param string|null $baseURI The base URI for the application
     * @return void
     */
    protected function setBaseURI(?string $baseURI = null): void
    {
        $baseURI = $baseURI ?? '';
        $baseURI  = $this->prepareBaseURI($baseURI);
        $parseURI = parse_url($baseURI);

        $this->basePath = isset($parseURI['path']) ? $parseURI['path'] : '';
        $this->baseURI = $baseURI ?? '';
    }


    /**
     * Gets the base URI
     * 
     * @return string The base URI
     */
    public function getBaseURI(): string
    {
        return $this->baseURI;
    }

    /**
     * Gets the base path
     * 
     * @return string The base path
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }


    /**
     * Gets the path relative to the base path
     * 
     * @return string The relative path
     */
    public function getRelativePath(): string
    {
        $requestedPath = $this->request->getUri()->getPath();
        $basePath = rtrim($this->getBasePath(), '/');

        // Normalize slashes
        $requestedPath = '/' . ltrim($requestedPath, '/');

        // Ensure basePath is prefix of requestedPath
        if ($basePath && str_starts_with($requestedPath, $basePath . '/')) {
            $relative = substr($requestedPath, strlen($basePath));
            return '/' . ltrim($relative, '/'); // always return path with leading slash
        }

        return $requestedPath; // fallback to full path if no match
    }
}
