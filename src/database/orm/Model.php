<?php


namespace Arbor\database\orm;


use Arbor\database\orm\Attributes;


abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';

    protected Attributes $attributes;
    protected bool $exists = false;

    protected $db;

    public function __construct($db, array $attributes = [], bool $exists = false)
    {
        $this->db = $db;
        $this->attributes = new Attributes($attributes, true);
        $this->exists = $exists;
    }

    // ------------------------------
    // CRUD
    // ------------------------------

    public function find(int|string $id): ?static
    {
        $row = $this->db->table($this->table)
            ->where($this->primaryKey, '=', $id)
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

        $instance->attributes->set($this->primaryKey, $id);
        $instance->exists = true;
        $instance->attributes->syncOriginal();

        return $instance;
    }

    public function update(array $attributes = []): bool
    {
        $this->attributes->fill($attributes);

        if (!$this->attributes->isDirty()) {
            return false;
        }

        $this->db->table($this->table)
            ->where($this->primaryKey, '=', $this->attributes->get($this->primaryKey))
            ->update($this->attributes->getDirty());

        $this->attributes->syncOriginal();
        return true;
    }

    public function delete(): bool
    {
        if (!$this->exists) return false;

        $deleted = $this->db->table($this->table)
            ->where($this->primaryKey, '=', $this->attributes->get($this->primaryKey))
            ->delete();

        if ($deleted) {
            $this->exists = false;
        }
        return (bool) $deleted;
    }

    // ------------------------------
    // Pass-through to Attributes
    // ------------------------------

    public function __get(string $key)
    {
        return $this->attributes->$key;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes->$key = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes->$key);
    }

    public function __unset(string $key): void
    {
        unset($this->attributes->$key);
    }

    public function toArray(): array
    {
        return $this->attributes->toArray();
    }
}
