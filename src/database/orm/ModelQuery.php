<?php

namespace Arbor\database\orm;

use BadMethodCallException;
use Arbor\database\Database;
use Arbor\database\QueryBuilder;


class ModelQuery
{
    protected QueryBuilder $builder;
    protected Database $database;
    protected string $model;


    public function __construct(Database $database, string $model)
    {
        $this->database = $database;
        $this->model = $model;
        $this->spawnBuilder();
    }


    protected function spawnBuilder(): QueryBuilder
    {
        $this->builder = $this->database->table($this->model::getTableName());
        return $this->builder;
    }


    public function hydrate(array $attributes)
    {
        $class = $this->model;
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


    public function create(array $attributes)
    {
        // insert into table, get back inserted-id
        $id = $this->builder->insert($attributes);

        $attributes = array_merge($attributes, ['id' => $id]);

        return $this->hydrate($attributes);
    }


    public function find(mixed $id, array $columns = ['*'])
    {
        // Get table + primary key from the model
        $primaryKey = $this->model::getPrimaryKey();

        // Run the query on QueryBuilder
        $record = $this->builder->select($columns)
            ->where($primaryKey, $id)
            ->first();

        // Hydrate into Model instance
        return $record
            ? $this->hydrate($record)
            : null;
    }


    public function get(?array $columns = null): array
    {
        // Run builder get
        $records = $this->builder->get($columns);

        // Hydrate each record into a model
        $models = [];
        foreach ($records as $record) {
            $models[] = $this->hydrate($record);
        }

        return $models;
    }


    public function first(?array $columns = null)
    {
        // Run builder first
        $record = $this->builder->select($columns)->first();

        // Hydrate into a model if found
        return $record
            ? $this->hydrate($record)
            : null;
    }
}
