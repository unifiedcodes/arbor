<?php

namespace Arbor\database\orm;


use Arbor\database\Database;
use Arbor\database\Row;


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


    public function find($id)
    {
        $row = $this->db->table($this->table)
            ->where($this->primaryKey, '=', $id);
    }
}
