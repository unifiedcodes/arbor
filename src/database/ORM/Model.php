<?php

namespace Arbor\database\orm;


use Arbor\database\Database;
use Arbor\database\orm\Record;


abstract class Model
{
    protected static string $table;
    protected static string $primaryKey = 'id';
    protected Database $db;


    public function __construct(Database $db)
    {
        $this->db = $db;

        // If child model didn’t set table, infer from class name
        if (!isset($this->table)) {
            $class = (new \ReflectionClass($this))->getShortName();
            $this->table = strtolower($class);
        }
    }


    public function find($id): Record|null
    {
        $row = $this->db->table($this->table)
            ->where($this->primaryKey, $id)->fetch();

        return $row ? $this->hydrate($row) : null;
    }


    protected function hydrate(array $row): Record
    {
        return new Record($row);
    }

    protected function hydrateAll(array $rows): array
    {
        return array_map(fn($row) => $this->hydrate($row), $rows);
    }


    public function all(): array
    {
        $rows = $this->db->table($this->table)->fetchAll();
        return $this->hydrateAll($rows);
    }


    public function create(array $attributes): Record
    {
        $id = $this->db->table($this->table)->insert($attributes);

        $record = new Record($attributes);
        $record->{$this->primaryKey} = $id;

        return $record;
    }


    public function update($id, array $attributes): bool
    {
        return $this->db->table($this->table)
            ->where($this->primaryKey, $id)
            ->update($attributes) > 0;
    }


    public function delete($id): bool
    {
        return $this->db->table($this->table)
            ->where($this->primaryKey, '=', $id)
            ->delete() > 0;
    }
}
