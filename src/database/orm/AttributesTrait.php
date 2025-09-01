<?php

namespace Arbor\database\orm;

use InvalidArgumentException;
use Arbor\database\orm\relations\Relationship;

trait AttributesTrait
{
    protected array $attributes = [];
    protected array $original = [];


    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }


    public function getAttribute(string $key, mixed $default = null): mixed
    {
        // Direct attribute exists
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        // Check if a method exists for this key (relationship)
        if (method_exists($this, $key)) {

            // Lazy load relationship
            if (!isset($this->relations[$key])) {
                $relation = $this->$key();

                // Auto-resolve if it is a Relationship
                if ($relation instanceof Relationship) {
                    $relation = $relation->resolve();
                }

                $this->relations[$key] = $relation;
            }

            return $this->relations[$key];
        }

        // Fallback to default value if provided
        if (func_num_args() === 2) {
            return $default;
        }

        throw new InvalidArgumentException(
            "The attribute or relation '{$key}' is not defined for the model '" . static::class . "'."
        );
    }


    public function resetAttributes(): void
    {
        $this->attributes = [];
        $this->syncOriginal();
    }


    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    // ------------------------------
    // Magic access
    // ------------------------------

    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }


    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }


    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }


    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    // ------------------------------
    // ArrayAccess
    // ------------------------------

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }


    public function offsetGet(mixed $offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }


    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->attributes[$offset] = $value;
    }


    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    // ------------------------------
    // JsonSerializable
    // ------------------------------

    public function jsonSerialize(): mixed
    {
        return $this->attributes;
    }

    // ------------------------------
    // Dirty checking
    // ------------------------------

    public function isDirty(): bool
    {
        return $this->attributes !== $this->original;
    }


    public function getDirty(): array
    {
        return array_diff_assoc($this->attributes, $this->original);
    }


    public function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    // ------------------------------
    // Utility
    // ------------------------------

    public function toArray(): array
    {
        return $this->attributes;
    }
}
