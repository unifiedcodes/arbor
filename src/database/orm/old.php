<?php

class orm
{
    public function find(int|string $id): ?static
    {
        $row = $this->db->table(static::$tableName)
            ->where($this->primaryKey, $id)
            ->first();

        return $row ? new static($row, true) : null;
    }

    public function create(array $attributes): static
    {
        $instance = new static($attributes, false);
        $id = $this->db->table(static::$tableName)->insertGetId($attributes);

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

        $this->db->table(static::$tableName)
            ->where($this->primaryKey, $this->get($this->primaryKey))
            ->update($this->getDirty());

        $this->syncOriginal();
        return true;
    }


    public function delete(): bool
    {
        if (!$this->exists) return false;

        $deleted = $this->db->table(static::$tableName)
            ->where($this->primaryKey, $this->get($this->primaryKey))
            ->delete();

        if ($deleted) {
            $this->exists = false;
        }

        return (bool) $deleted;
    }
}
