<?php

namespace Arbor\database\orm;

use ArrayAccess;
use JsonSerializable;


class Attributes implements ArrayAccess, JsonSerializable
{
    protected array $attributes = [];
    protected array $original = [];

    public function __construct(array $attributes = [], bool $trackOriginal = true)
    {
        $this->fill($attributes);
        if ($trackOriginal) {
            $this->original = $this->attributes;
        }
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    // ------------------------------
    // Magic access
    // ------------------------------

    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
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

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }
}
