<?php

namespace Arbor\http\components;

/**
 * Class for handling HTTP component attributes.
 * 
 * Provides a storage mechanism for key-value pairs with immutable operations
 * that return new instances upon modification.
 * 
 * @package Arbor\http\components
 */
class Attributes
{
    /**
     * Storage for attribute items.
     * 
     * @var array<string, mixed>
     */
    protected array $items = [];

    /**
     * Constructor.
     * 
     * @param array<string, mixed> $items Initial attributes
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Get all attributes.
     * 
     * @return array<string, mixed> All attribute items
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Check if attribute exists.
     * 
     * @param string $key The attribute key to check
     * @return bool True if the attribute exists, false otherwise
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Get attribute value or default.
     * 
     * @param string $key The attribute key to retrieve
     * @param mixed $default Default value if attribute doesn't exist
     * @return mixed The attribute value or default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    /**
     * Return a new instance with the given attribute.
     * 
     * @param string $key The attribute key to set
     * @param mixed $value The value to set
     * @return static New instance with the attribute added/updated
     */
    public function with(string $key, mixed $value): static
    {
        $clone = clone $this;
        $clone->items[$key] = $value;
        return $clone;
    }

    /**
     * Return a new instance without the given attribute.
     * 
     * @param string $key The attribute key to remove
     * @return static New instance with the attribute removed
     */
    public function without(string $key): static
    {
        $clone = clone $this;
        unset($clone->items[$key]);
        return $clone;
    }
}
