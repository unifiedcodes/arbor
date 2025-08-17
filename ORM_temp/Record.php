<?php

namespace Arbor\database\orm;

use ArrayAccess;
use JsonSerializable;

/**
 * Record class provides a flexible data container with array and object access patterns.
 * 
 * This class implements both ArrayAccess and JsonSerializable interfaces to provide
 * seamless interaction with data attributes through multiple access methods:
 * - Object property access ($record->property)
 * - Array access ($record['key'])
 * - JSON serialization support
 * 
 * @implements ArrayAccess<mixed, mixed>
 * @implements JsonSerializable
 */
class Record implements ArrayAccess, JsonSerializable
{
    /**
     * Internal storage for all record attributes
     * 
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * Constructor initializes the record with optional attributes
     * 
     * @param array<string, mixed> $attributes Initial attributes to populate the record
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Magic getter for accessing attributes as object properties
     * 
     * Allows accessing record attributes using object notation: $record->key
     * Returns null if the attribute doesn't exist.
     * 
     * @param string $key The attribute name to retrieve
     * @return mixed The attribute value or null if not found
     */
    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Magic setter for setting attributes as object properties
     * 
     * Allows setting record attributes using object notation: $record->key = $value
     * 
     * @param string $key The attribute name to set
     * @param mixed $value The value to assign to the attribute
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Magic isset checker for attribute existence
     * 
     * Allows using isset() on object properties: isset($record->key)
     * 
     * @param string $key The attribute name to check
     * @return bool True if the attribute exists and is not null
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Magic unset method for removing attributes
     * 
     * Allows using unset() on object properties: unset($record->key)
     * 
     * @param string $key The attribute name to remove
     * @return void
     */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    /**
     * ArrayAccess: Check if an offset exists
     * 
     * Allows using isset() with array notation: isset($record['key'])
     * 
     * @param mixed $offset The array key to check
     * @return bool True if the offset exists and is not null
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * ArrayAccess: Get value at specified offset
     * 
     * Allows array-style access for reading: $value = $record['key']
     * Returns null if the offset doesn't exist.
     * 
     * @param mixed $offset The array key to retrieve
     * @return mixed The value at the offset or null if not found
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }

    /**
     * ArrayAccess: Set value at specified offset
     * 
     * Allows array-style access for writing: $record['key'] = $value
     * 
     * @param mixed $offset The array key to set
     * @param mixed $value The value to assign
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->attributes[$offset] = $value;
    }

    /**
     * ArrayAccess: Unset value at specified offset
     * 
     * Allows array-style removal: unset($record['key'])
     * 
     * @param mixed $offset The array key to remove
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Get all attributes as an associative array
     * 
     * Provides access to the complete internal attributes array.
     * Useful for bulk operations or when you need all data at once.
     * 
     * @return array<string, mixed> All record attributes
     */
    public function all(): array
    {
        return $this->attributes;
    }

    /**
     * JsonSerializable: Specify data to serialize to JSON
     * 
     * When json_encode() is called on this object, this method determines
     * what data should be included in the JSON representation.
     * 
     * @return array<string, mixed> Data to be serialized as JSON
     */
    public function jsonSerialize(): array
    {
        return $this->attributes;
    }
}
