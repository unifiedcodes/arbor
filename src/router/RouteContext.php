<?php

namespace Arbor\router;

use Arbor\router\Node;
use Arbor\router\Meta;

/**
 * Represents the context of a matched route.
 * 
 * Contains all information about a route match including the path, HTTP verb,
 * status code, handler, middlewares, parameters, and metadata.
 */
final class RouteContext
{
    /**
     * Creates a new RouteContext instance.
     * 
     * @param string $path The matched route path
     * @param string $verb The HTTP verb (GET, POST, etc.)
     * @param int $statusCode The HTTP status code for this context
     * @param Node|null $node The matched route node, if any
     * @param Meta|null $meta Route metadata, if any
     * @param array $parameters Extracted route parameters
     * @param string|array $handler The route handler (controller/callable)
     * @param array $middlewares Array of middleware to apply
     */
    public function __construct(
        private string $path,
        private string $verb,
        private int $statusCode,
        private ?Node $node,
        private ?Meta $meta,
        private array $parameters,
        private string|array $handler,
        private array $middlewares,
        private string $routeName = ''
    ) {}
    
    /* -----------------------------------------------------------------
     | Named constructors
     |-----------------------------------------------------------------*/

    /**
     * Creates an error route context.
     * 
     * @param string $path The requested path
     * @param string $verb The HTTP verb
     * @param int $statusCode The error status code (e.g., 404, 405)
     * @param string|array $handler The error handler
     * @return self
     */
    public static function error(
        string $path,
        string $verb,
        int $statusCode,
        string|array $handler,
    ): self {
        return new self(
            path: $path,
            verb: $verb,
            statusCode: $statusCode,
            node: null,
            meta: null,
            parameters: [],
            handler: $handler,
            middlewares: [],
        );
    }
    
    /* -----------------------------------------------------------------
     | Identity
     |-----------------------------------------------------------------*/

    /**
     * Gets the matched route path.
     * 
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Gets the HTTP verb.
     * 
     * @return string
     */
    public function verb(): string
    {
        return $this->verb;
    }

    /**
     * Gets the HTTP status code.
     * 
     * @return int
     */
    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Checks if this is an error context (status >= 400).
     * 
     * @return bool
     */
    public function isError(): bool
    {
        return $this->statusCode >= 400;
    }

    /**
     * Checks if this is a successful context (status < 400).
     * 
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode < 400;
    }

    /* -----------------------------------------------------------------
     | Handler & middlewares
     |-----------------------------------------------------------------*/

    /**
     * Gets the route handler.
     * 
     * @return string|array
     */
    public function handler(): string|array
    {
        return $this->handler;
    }

    /**
     * Gets the middleware stack.
     * 
     * @return array
     */
    public function middlewares(): array
    {
        $normalized = $this->normalizeMiddlewares($this->middlewares);
        asort($normalized, SORT_NUMERIC);
        return array_keys($normalized); // associative, ordered
    }


    public function middlewareStack(): array
    {
        return array_keys($this->middlewares);
    }


    private function normalizeMiddlewares(array $middlewares): array
    {
        $normalized = [];

        foreach ($middlewares as $key => $value) {
            if (is_int($key)) {
                // ['AuthMiddleware']
                $normalized[$value] = 0;
            } else {
                // ['AuthMiddleware' => 10]
                $normalized[$key] = (int) $value;
            }
        }

        return $normalized;
    }

    /**
     * Creates a new instance with merged middlewares.
     * 
     * Merges the provided middlewares with existing ones, maintaining uniqueness.
     * New middlewares are prepended to the existing stack.
     * 
     * @param array $middlewares Additional middlewares to merge
     * @return self
     */
    public function withMergedMiddlewares(array $middlewares): self
    {
        $clone = clone $this;

        // Normalize BOTH sides first
        $incoming = $this->normalizeMiddlewares($middlewares);
        $existing = $this->normalizeMiddlewares($this->middlewares);

        /**
         * Incoming (route) overrides existing (group)
         * Keys are middleware class names
         */
        $clone->middlewares = array_replace($existing, $incoming);

        return $clone;
    }
    
    /* -----------------------------------------------------------------
     | Parameters
     |-----------------------------------------------------------------*/

    /**
     * Gets all route parameters.
     * 
     * @return array
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    /**
     * Gets a specific route parameter.
     * 
     * @param string $key The parameter name
     * @param mixed $default Default value if parameter doesn't exist
     * @return mixed
     */
    public function parameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Checks if a parameter exists.
     * 
     * @param string $key The parameter name
     * @return bool
     */
    public function hasParameter(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /* -----------------------------------------------------------------
     | Meta & attributes
     |-----------------------------------------------------------------*/

    /**
     * Gets the route metadata.
     * 
     * @return Meta|null
     */
    public function meta(): ?Meta
    {
        return $this->meta;
    }

    /**
     * Gets all route attributes from metadata.
     * 
     * @return array
     */
    public function attributes(): array
    {
        return $this->meta?->attributes() ?? [];
    }

    /**
     * Gets a specific route attribute from metadata.
     * 
     * @param string $key The attribute name
     * @param mixed $default Default value if attribute doesn't exist
     * @return mixed
     */
    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->meta?->getAttribute($key, $default);
    }

    /**
     * Checks if an attribute exists in metadata.
     * 
     * @param string $key The attribute name
     * @return bool
     */
    public function hasAttribute(string $key): bool
    {
        return $this->meta?->hasAttribute($key) ?? false;
    }

    /**
     * Creates a new instance with merged attributes.
     * 
     * Merges the provided attributes with existing ones, maintaining uniqueness.
     * existing attributes override the incoming attributes.
     * 
     * @param array $attributes Additional attributes to merge
     * @return self
     */
    public function withMergedAttributes(array $attributes): self
    {
        $clone = clone $this;

        $existing = $this->attributes();
        $incoming = $attributes;

        /**
         * Merge rule:
         * incoming < existing
         */
        $merged = array_replace($incoming, $existing);

        // Meta may be null on error routes
        if ($clone->meta) {
            $clone->meta->setAttributes($merged);
        }

        return $clone;
    }

    /* -----------------------------------------------------------------
     | Node & grouping
     |-----------------------------------------------------------------*/

    /**
     * Gets the matched route node.
     * 
     * @return Node|null
     */
    public function node(): ?Node
    {
        return $this->node;
    }

    /**
     * Gets the group ID from the route node.
     * 
     * @return string|null
     */
    public function groupId(): ?string
    {
        return $this->node?->getGroupId();
    }

    /**
     * get the route name of current node.
     * 
     * @return string|null
     */
    public function nodeName()
    {
        return $this->node?->getName();
    }


    public function withRouteName(string $routeName): self
    {
        $clone = clone $this;

        $clone->routeName = $routeName;

        return $clone;
    }


    public function routeName()
    {
        return $this->routeName;
    }
}
