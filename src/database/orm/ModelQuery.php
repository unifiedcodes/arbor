<?php

namespace Arbor\database\orm;


use RuntimeException;
use BadMethodCallException;
use Arbor\database\Database;
use Arbor\database\QueryBuilder;


class ModelQuery
{
    protected QueryBuilder $builder;
    protected Database $database;
    protected string $model;
    protected array $withRelations = [];


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


    public function with(array|string $related): static
    {
        $this->withRelations = is_array($related) ? $related : [$related];
        return $this;
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

        $this->eagerRelations($models);

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


    protected function eagerRelations(array $models): array
    {
        if (empty($this->withRelations)) {
            return $models;
        }

        foreach ($this->withRelations as $relation) {
            $this->loadRelation($relation, $models);
        }

        return $models;
    }


    protected function loadRelation(string $relation, array $models)
    {
        if (empty($models)) {
            return;
        }

        $first = reset($models);

        if (!method_exists($first, $relation)) {
            throw new RuntimeException("Relation {$relation} is not defined on model " . get_class($first));
        }

        $relationObj = $first->$relation();
        $related = $relationObj->eagerLoad($relation, $models);


        return $related;
    }
}
