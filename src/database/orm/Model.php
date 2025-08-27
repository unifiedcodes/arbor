<?php

namespace Arbor\database\orm;

use Exception;
use ArrayAccess;
use JsonSerializable;
use Arbor\database\orm\ModelQuery;
use Arbor\database\Database;


abstract class Model implements ArrayAccess, JsonSerializable
{
    protected static ?string $tableName = null;
    protected static Database $database;

    protected array $attributes = [];
    protected array $original = [];
    protected string $primaryKey = 'id';
    protected bool $exists = false;


    public function __construct(array $attributes = [], bool $exists = false)
    {
        if (!static::$tableName) {
            $classname = get_class($this);
            throw new Exception("table name is not defined by Model Class: '{$classname}'");
        }

        if (!static::$database) {
            throw new Exception("Database Instance is not bound with Model Class");
        }

        $this->fill($attributes);
        $this->syncOriginal();
        $this->exists = $exists;
    }


    public static function setDatabase(Database $db): void
    {
        static::$database = $db;
    }


    public static function getDatabase(): Database
    {
        return static::$database;
    }


    public static function tableName(): string
    {
        return static::$tableName;
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


    public function all(): array
    {
        // let the hydration and query be handled by ModelQuery class.

        $rows = static::query()->get();
        return array_map(fn($row) => new static($row, true), $rows);
    }


    public static function query(): ModelQuery
    {
        return new ModelQuery(static::getDatabase(), static::class);
    }


    public static function __callStatic($method, $args)
    {
        return static::query()->$method(...$args);
    }
}
