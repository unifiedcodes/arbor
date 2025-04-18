<?php

namespace Arbor\http;


use Arbor\http\components\Headers;
use Arbor\http\components\Uri;
use Arbor\http\components\Attributes;
use Arbor\http\components\Stream;
use Arbor\http\traits\HeaderTrait;
use Arbor\http\traits\BodyTrait;


/**
 * Request class representing an HTTP request message.
 * 
 * This class implements PSR-7 compatible request functionality with immutability
 * for all state-changing operations.
 * 
 * @package Arbor\http
 */

class Request
{
    use HeaderTrait;
    use BodyTrait;

    /**
     * HTTP protocol version
     *
     * @var string
     */
    protected string $protocolVersion = '1.1';

    /**
     * HTTP request target
     *
     * @var string|null
     */
    protected ?string $requestTarget = null;

    /**
     * HTTP method
     *
     * @var string
     */
    protected string $method;

    /**
     * URI instance
     *
     * @var Uri
     */
    protected Uri $uri;

    /**
     * Request attributes collection
     *
     * @var Attributes
     */
    protected Attributes $attributes;

    /**
     * Request constructor.
     *
     * Creates a new Request instance with the provided parameters.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param Uri|string|null $uri URI for the request
     * @param Headers|array $headers Request headers
     * @param Stream|string|null $body Request body
     * @param Attributes|array $attributes Request attributes
     * @param string|null $version Protocol version
     * 
     * @throws \InvalidArgumentException If the method is invalid
     */
    public function __construct(
        string $method = 'GET',
        Uri|string|null $uri = null,
        Headers|array $headers = [],
        Stream|string|null $body = null,
        Attributes|array $attributes = [],
        ?string $version = '1.1',
    ) {
        // Convert string URI to Uri object if needed
        if (!$uri instanceof Uri) {
            $uri = new Uri($uri ?? '');
        }

        // Convert array to Headers object if needed
        if (!$headers instanceof Headers) {
            $headers = new Headers((array) $headers);
        }

        // Convert array to Attributes object if needed
        if (!$attributes instanceof Attributes) {
            $attributes = new Attributes((array) $attributes);
        }

        $this->attributes = $attributes;
        $this->method = $this->filterMethod($method);
        $this->uri = $uri;
        $this->headers = $headers;

        // ensureStreamBody is from BodyTrait
        $this->body = $this->ensureStreamBody($body);
        $this->protocolVersion = $version ?? '1.1';

        // Set Host header if not already present and URI has a host
        if (!$this->hasHeader('Host') && $this->uri->getHost()) {
            $this->updateHostHeaderFromUri();
        }
    }

    /**
     * Updates the Host header based on URI's host and port
     * 
     * @return void
     */
    protected function updateHostHeaderFromUri(): void
    {
        $host = $this->uri->getHost();
        if ($host) {
            $port = $this->uri->getPort();
            if ($port) {
                $host .= ':' . $port;
            }
            $this->headers->set('Host', $host);
        }
    }

    /**
     * Gets the protocol version
     * 
     * @return string The HTTP protocol version
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * Returns a new instance with the specified protocol version
     * 
     * @param string $version HTTP protocol version
     * @return self
     */
    public function withProtocolVersion(string $version): self
    {
        if ($this->protocolVersion === $version) {
            return $this;
        }

        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    /**
     * Gets the request target
     * 
     * @return string The request target
     */
    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($target === '') {
            $target = '/';
        }

        $query = $this->uri->getQuery();
        if ($query !== '') {
            $target .= '?' . $query;
        }

        return $target;
    }

    /**
     * Returns a new instance with the specified request target
     * 
     * @param string $requestTarget The request target
     * @return self
     */
    public function withRequestTarget(string $requestTarget): self
    {
        if ($this->requestTarget === $requestTarget) {
            return $this;
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    /**
     * Gets the HTTP method
     * 
     * @return string The HTTP method
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Returns a new instance with the specified HTTP method
     * 
     * @param string $method The HTTP method
     * @return self
     * @throws \InvalidArgumentException For invalid HTTP methods
     */
    public function withMethod(string $method): self
    {
        $method = $this->filterMethod($method);

        if ($this->method === $method) {
            return $this;
        }

        $new = clone $this;
        $new->method = $method;
        return $new;
    }

    /**
     * Gets the URI instance
     * 
     * @return Uri The URI instance
     */
    public function getUri(): Uri
    {
        return $this->uri;
    }

    /**
     * Returns a new instance with the specified URI
     * 
     * @param Uri $uri The new URI
     * @param bool $preserveHost Whether to preserve the Host header
     * @return self
     */
    public function withUri(Uri $uri, bool $preserveHost = false): self
    {
        if ($this->uri === $uri) {
            return $this;
        }

        $new = clone $this;
        $new->uri = $uri;

        if (!$preserveHost || !$this->hasHeader('Host')) {
            $new->updateHostHeaderFromUri();
        }

        return $new;
    }

    /**
     * Filters and normalizes the HTTP method
     * 
     * @param string $method The HTTP method
     * @return string The normalized HTTP method
     * @throws \InvalidArgumentException For invalid HTTP methods
     */
    protected function filterMethod(string $method): string
    {
        if (empty($method)) {
            throw new \InvalidArgumentException('Method must be a non-empty string');
        }

        return strtoupper($method);
    }

    /**
     * Gets all request attributes
     * 
     * @return array All attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes->all();
    }

    /**
     * Gets a specific request attribute
     * 
     * @param string $name Attribute name
     * @param mixed $default Default value if attribute is not found
     * @return mixed The attribute value or default
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes->get($name, $default);
    }

    /**
     * Returns a new instance with the specified attribute
     * 
     * @param string $name Attribute name
     * @param mixed $value Attribute value
     * @return self
     */
    public function withAttribute(string $name, mixed $value): self
    {
        $new = clone $this;
        $new->attributes = $this->attributes->with($name, $value);
        return $new;
    }

    /**
     * Returns a new instance without the specified attribute
     * 
     * @param string $name Attribute name
     * @return self
     */
    public function withoutAttribute(string $name): self
    {
        if (!$this->attributes->has($name)) {
            return $this;
        }

        $new = clone $this;
        $new->attributes = $this->attributes->without($name);
        return $new;
    }
}
