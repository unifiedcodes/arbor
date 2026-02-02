<?php

namespace Arbor\database\orm;

use RuntimeException;
use BadMethodCallException;
use Arbor\database\Database;
use Arbor\database\QueryBuilder;

/**
 * ModelQuery - ORM Query Builder for Model Operations
 * 
 * This class provides a fluent interface for building and executing database queries
 * on ORM models. It acts as a bridge between the QueryBuilder and Model instances,
 * handling model hydration and eager loading of relationships.
 * 
 * @package Arbor\database\orm
 */
class ModelQuery
{
    /**
     * The underlying query builder instance
     * 
     * @var QueryBuilder
     */
    protected QueryBuilder $builder;

    /**
     * The database connection instance
     * 
     * @var Database
     */
    protected Database $database;

    /**
     * The fully qualified class name of the model
     * 
     * @var string
     */
    protected string $model;

    /**
     * Array of relationship names to eager load
     * 
     * @var array
     */
    protected array $withRelations = [];

    /**
     * Create a new ModelQuery instance
     * 
     * @param Database $database The database connection
     * @param string $model The fully qualified model class name
     */
    public function __construct(Database $database, string $model)
    {
        $this->database = $database;
        $this->model = $model;
        $this->spawnBuilder();
    }

    /**
     * Create and configure a new QueryBuilder instance for the model's table
     * 
     * @return QueryBuilder The configured query builder
     */
    protected function spawnBuilder(): QueryBuilder
    {
        $this->builder = $this->database->table($this->model::getTableName());
        return $this->builder;
    }

    /**
     * Specify relationships to eager load with the query results
     * 
     * @param array|string $related Single relationship name or array of relationship names
     * @return static Returns self for method chaining
     */
    public function with(array|string $related): static
    {
        $this->withRelations = is_array($related) ? $related : [$related];
        return $this;
    }

    /**
     * Create a new model instance from database attributes
     * 
     * @param array $attributes The database row attributes
     * @return object New model instance populated with the given attributes
     */
    public function hydrate(array $attributes)
    {
        $class = $this->model;
        return new $class($attributes, true);
    }

    /**
     * Handle dynamic method calls by forwarding to the QueryBuilder
     * 
     * This magic method allows ModelQuery to act as a proxy for QueryBuilder methods
     * while maintaining the fluent interface and returning appropriate types.
     * 
     * @param string $method The method name to call
     * @param array $arguments The method arguments
     * @return mixed Returns ModelQuery for chainable methods, otherwise returns QueryBuilder result
     * @throws BadMethodCallException When the method doesn't exist on QueryBuilder
     */
    public function __call(string $method, array $arguments)
    {
        if (!method_exists($this->builder, $method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        $result = $this->builder->$method(...$arguments);

        // If the builder returns itself (for chaining), we return ModelQuery
        return $result === $this->builder ? $this : $result;
    }

    /**
     * Create a new model record in the database
     * 
     * @param array $attributes The attributes to insert
     * @return object The newly created model instance with the generated ID
     */
    public function create(array $attributes)
    {
        // insert into table, get back inserted-id
        $id = $this->builder->insert($attributes);

        $attributes = array_merge($attributes, ['id' => $id]);

        return $this->hydrate($attributes);
    }

    /**
     * Find a model by its primary key
     * 
     * @param mixed $id The primary key value to search for
     * @param array $columns The columns to select (defaults to all columns)
     * @return object|null The model instance if found, null otherwise
     */
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

    /**
     * Execute the query and return all matching models
     * 
     * @param array|null $columns The columns to select (null means all columns)
     * @return array Array of hydrated model instances
     */
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

    /**
     * Execute the query and return the first matching model
     * 
     * @param array|null $columns The columns to select (null means all columns)
     * @return object|null The first model instance if found, null otherwise
     */
    public function first(?array $columns = null)
    {
        $record = $this->builder->select($columns)->first();

        if (!$record) {
            return null;
        }

        $model = $this->hydrate($record);

        // Eager load relations for a single model
        if (!empty($this->withRelations)) {
            $this->eagerRelations([$model]);
        }

        return $model;
    }

    /**
     * Load eager relationships for a collection of models
     * 
     * @param array $models Array of model instances to load relationships for
     * @return array The same array of models with relationships loaded
     */
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

    /**
     * Load a specific relationship for a collection of models
     * 
     * @param string $relation The name of the relationship method
     * @param array $models Array of model instances to load the relationship for
     * @return mixed The loaded relationship data
     * @throws RuntimeException When the relationship method doesn't exist on the model
     */
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
