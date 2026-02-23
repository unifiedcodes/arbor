<?php

namespace Arbor\scope;

use Arbor\scope\events\DisposeError;
use Arbor\facades\Events;
use Throwable;

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
    private array $disposables = [];
    private bool $disposed = false;


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


    public function registerDisposable(Disposable $disposable): void
    {
        if ($this->disposed) {
            $disposable->dispose();
            return;
        }

        $this->disposables[] = $disposable;
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


    public function dispose(int $depth = 0): void
    {
        if ($this->disposed) {
            return;
        }

        foreach (array_reverse($this->disposables) as $disposable) {
            try {
                $disposable->dispose();
            } catch (Throwable $e) {
                // fire an event.
                Events::dispatch(new DisposeError($disposable, $e, $depth));
            }
        }

        $this->disposables = [];
        $this->items = [];
        $this->disposed = true;
    }
}
