<?php

namespace Arbor\database\orm;

use BadMethodCallException;
use Arbor\database\Database;
use Arbor\database\QueryBuilder;


class ModelQuery
{
    protected QueryBuilder $builder;
    protected Database $database;
    protected string $modelClass;


    public function __construct(Database $database, string $modelClass)
    {
        $this->database = $database;
        $this->modelClass = $modelClass;
        $this->builder = $database->table($modelClass::getTableName());
    }


    public function create(array $attributes)
    {
        // insert into table, get back auto-id
        $id = $this->builder->insert($attributes);

        $attributes = array_merge($attributes, ['id' => $id]);

        return $this->hydrate($attributes);
    }


    protected function hydrate(array $attributes)
    {
        $class = $this->modelClass;
        return new $class($attributes, true);
    }


    public function __call(string $method, array $arguments)
    {
        if (!method_exists($this->builder, $method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        $result = $this->builder->$method(...$arguments);

        // If the builder returns itself (for chaining), we return ModelQuery
        return $result === $this->builder ? $this : $result;
    }
}
