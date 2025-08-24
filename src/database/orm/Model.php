<?php


namespace Arbor\database\orm;


use ArrayAccess;
use JsonSerializable;


abstract class Model implements ArrayAccess, JsonSerializable
{
    protected array $attributes = [];
    protected array $original = [];

    protected string $table;
    protected string $primaryKey = 'id';

    protected bool $exists = false;

    protected $db;

    public function __construct($db, array $attributes = [], bool $exists = false)
    {
        $this->db = $db;
        $this->fill($attributes);
        $this->syncOriginal();
        $this->exists = $exists;
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


    public function find(int|string $id): ?static
    {
        $row = $this->db->table($this->table)
            ->where($this->primaryKey, $id)
            ->first();

        return $row ? new static($this->db, $row, true) : null;
    }


    public function all(): array
    {
        $rows = $this->db->table($this->table)->get();
        return array_map(fn($row) => new static($this->db, $row, true), $rows);
    }


    public function create(array $attributes): static
    {
        $instance = new static($this->db, $attributes, false);
        $id = $this->db->table($this->table)->insertGetId($attributes);

        $instance->set($this->primaryKey, $id);
        $instance->exists = true;
        $instance->syncOriginal();

        return $instance;
    }


    public function update(array $attributes = []): bool
    {
        if (!$this->exists) return false;

        $this->fill($attributes);

        if (!$this->isDirty()) {
            return false;
        }

        $this->db->table($this->table)
            ->where($this->primaryKey, $this->get($this->primaryKey))
            ->update($this->getDirty());

        $this->syncOriginal();
        return true;
    }


    public function delete(): bool
    {
        if (!$this->exists) return false;

        $deleted = $this->db->table($this->table)
            ->where($this->primaryKey, $this->get($this->primaryKey))
            ->delete();

        if ($deleted) {
            $this->exists = false;
        }

        return (bool) $deleted;
    }
}
