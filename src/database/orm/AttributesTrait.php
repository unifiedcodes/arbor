<?php

namespace Arbor\database\orm;

/**
 * Trait AttributesTrait
 * 
 * Provides attribute management functionality with magic access methods,
 * array access implementation, JSON serialization, and dirty checking capabilities.
 * 
 * This trait allows objects to store and manage key-value attributes with support for:
 * - Magic property access (__get, __set, __isset, __unset)
 * - Array access (ArrayAccess interface methods)
 * - JSON serialization (JsonSerializable interface)
 * - Dirty state tracking for change detection
 * 
 * @package Arbor\database\orm
 */
trait AttributesTrait
{
    /**
     * Array of current attribute values
     * 
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * Array of original attribute values for dirty checking
     * 
     * @var array<string, mixed>
     */
    protected array $original = [];

    /**
     * Fill the attributes with the given array of key-value pairs
     * 
     * @param array<string, mixed> $attributes Array of attributes to fill
     * @return static Returns the current instance for method chaining
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /**
     * Get an attribute value by key
     * 
     * @param string $key The attribute key
     * @param mixed $default Default value to return if key doesn't exist
     * @return mixed The attribute value or default if not found
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Set an attribute value by key
     * 
     * @param string $key The attribute key
     * @param mixed $value The value to set
     * @return void
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Reset all attributes to empty array and sync original state
     * 
     * @return void
     */
    public function resetAttributes(): void
    {
        $this->attributes = [];
        $this->syncOriginal();
    }

    // ------------------------------
    // Magic access
    // ------------------------------

    /**
     * Magic getter for attribute access
     * 
     * Allows accessing attributes as object properties: $obj->name
     * 
     * @param string $key The attribute key
     * @return mixed The attribute value or null if not found
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter for attribute access
     * 
     * Allows setting attributes as object properties: $obj->name = 'value'
     * 
     * @param string $key The attribute key
     * @param mixed $value The value to set
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Magic isset check for attribute existence
     * 
     * Allows using isset() on object properties: isset($obj->name)
     * 
     * @param string $key The attribute key
     * @return bool True if attribute exists, false otherwise
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Magic unset for attribute removal
     * 
     * Allows using unset() on object properties: unset($obj->name)
     * 
     * @param string $key The attribute key
     * @return void
     */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    // ------------------------------
    // ArrayAccess
    // ------------------------------

    /**
     * Check if an attribute exists (ArrayAccess implementation)
     * 
     * Allows using isset() with array syntax: isset($obj['name'])
     * 
     * @param mixed $offset The attribute key
     * @return bool True if attribute exists, false otherwise
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Get an attribute value (ArrayAccess implementation)
     * 
     * Allows array-style access for reading: $obj['name']
     * 
     * @param mixed $offset The attribute key
     * @return mixed The attribute value or null if not found
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }

    /**
     * Set an attribute value (ArrayAccess implementation)
     * 
     * Allows array-style access for writing: $obj['name'] = 'value'
     * 
     * @param mixed $offset The attribute key
     * @param mixed $value The value to set
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->attributes[$offset] = $value;
    }

    /**
     * Unset an attribute (ArrayAccess implementation)
     * 
     * Allows array-style removal: unset($obj['name'])
     * 
     * @param mixed $offset The attribute key
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    // ------------------------------
    // JsonSerializable
    // ------------------------------

    /**
     * Return data which should be serialized by json_encode()
     * 
     * Implementation of JsonSerializable interface
     * 
     * @return mixed Data that can be serialized by json_encode()
     */
    public function jsonSerialize(): mixed
    {
        return $this->attributes;
    }

    // ------------------------------
    // Dirty checking
    // ------------------------------

    /**
     * Check if any attributes have been modified since last sync
     * 
     * @return bool True if attributes differ from original state, false otherwise
     */
    public function isDirty(): bool
    {
        return $this->attributes !== $this->original;
    }

    /**
     * Get array of attributes that have been modified
     * 
     * Returns only the attributes that are different from the original state
     * 
     * @return array<string, mixed> Array of modified attributes
     */
    public function getDirty(): array
    {
        return array_diff_assoc($this->attributes, $this->original);
    }

    /**
     * Synchronize the original state with current attributes
     * 
     * Sets the original array to match current attributes, effectively
     * marking the current state as "clean" (not dirty)
     * 
     * @return void
     */
    public function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    // ------------------------------
    // Utility
    // ------------------------------

    /**
     * Get all attributes as a plain array
     * 
     * @return array<string, mixed> All current attributes
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
