<?php

namespace Arbor\router;


use Exception;

/**
 * Class Meta
 *
 * Encapsulates meta-information for a route, including the handler and associated middlewares.
 *
 * @package Arbor\router
 */
class Meta
{
    /**
     * The route handler which can be a callable, string, array, or null.
     *
     * @var callable|string|array|null
     */
    protected mixed $handler = null;

    /**
     * An array of middlewares associated with the route.
     *
     * @var array
     */
    protected array $middlewares = [];

    protected array $attributes = [];

    /**
     * Meta constructor.
     *
     * Initializes the Meta instance with the given options.
     * Expects an array with a 'handler' key and optionally a 'middlewares' key.
     *
     * @param array $options The options array containing the handler and middlewares.
     *
     * @throws Exception If required options are missing or invalid.
     */
    public function __construct(array $options)
    {
        if (!isset($options['handler'])) {
            throw new Exception("Handler option is required.");
        }
        $this->handler = $this->setHandler($options['handler']);
        $this->middlewares = $options['middlewares'] ?? [];
    }

    /**
     * Sets the route handler.
     *
     * The handler can be a callable, string, array, or null.
     *
     * @param callable|string|array|null $handler The handler to set.
     *
     * @return callable|string|array|null The set handler.
     */
    public function setHandler(callable|string|array|null $handler = null): callable|string|array|null
    {
        $this->handler = $handler;
        return $this->handler;
    }

    public function setAttributes($attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * Retrieves the route handler.
     *
     * @return callable|string|array|null The current route handler.
     */
    public function getHandler(): callable|string|array|null
    {
        return $this->handler;
    }

    /**
     * Retrieves the middlewares associated with the route.
     *
     * @return array The list of middlewares.
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Determine if the given attribute exists.
     */
    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Get a single attribute value.
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Get all attributes (alias for getAttributes, optional).
     */
    public function attributes(): array
    {
        return $this->attributes;
    }
}
