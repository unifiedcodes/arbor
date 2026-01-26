<?php

namespace Arbor\scope;

/**
 * Frame class for managing a scoped collection of key-value items.
 * 
 * This class provides a simple in-memory storage mechanism for storing
 * and retrieving arbitrary data using string keys.
 */
final class Frame
{
    /**
     * Array to store the key-value items.
     * 
     * @var array
     */
    private array $items = [];

    /**
     * Sets a value in the frame with the given key.
     * 
     * If the key already exists, its value will be overwritten.
     * 
     * @param string $key The key to store the value under
     * @param mixed $value The value to store (can be any type)
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->items[$key] = $value;
    }

    /**
     * Checks if a key exists in the frame.
     * 
     * @param string $key The key to check for
     * @return bool True if the key exists, false otherwise
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Retrieves a value from the frame by key.
     * 
     * Returns null if the key does not exist.
     * 
     * @param string $key The key to retrieve
     * @return mixed The value associated with the key, or null if not found
     */
    public function get(string $key): mixed
    {
        return $this->items[$key] ?? null;
    }

    /**
     * Retrieves all items stored in the frame.
     * 
     * @return array An associative array containing all key-value pairs
     */
    public function all(): array
    {
        return $this->items;
    }
}
