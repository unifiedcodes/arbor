<?php

namespace Arbor\database\orm\relations;

use Arbor\database\orm\Model;
use Arbor\database\orm\ModelQuery;
use BadMethodCallException;

/**
 * Abstract base class for defining relationships between models in the ORM.
 * 
 * This class provides the foundation for all relationship types (e.g., HasOne, HasMany, BelongsTo)
 * by establishing the connection between a parent model and a query builder. It handles the core
 * functionality needed for relationship resolution, eager loading, and query delegation.
 *
 * @package Arbor\database\orm\relations
 * @abstract
 */
abstract class Relationship
{
    /**
     * The parent model instance that owns this relationship.
     *
     * @var Model
     */
    protected Model $parent;

    /**
     * The query builder instance used to construct database queries for this relationship.
     *
     * @var ModelQuery
     */
    protected ModelQuery $query;

    /**
     * Initialize a new relationship instance.
     *
     * @param Model $parent The parent model that owns this relationship
     * @param ModelQuery $query The query builder for the related model
     */
    public function __construct(Model $parent, ModelQuery $query)
    {
        $this->parent = $parent;
        $this->query = $query;
    }

    /**
     * Resolve the relationship and return the related model(s).
     * 
     * This method should be implemented by concrete relationship classes to define
     * how the relationship is resolved (e.g., executing the query and returning results).
     *
     * @return mixed The resolved relationship result (Model, Collection, etc.)
     */
    abstract public function resolve(); // default resolution

    /**
     * Perform eager loading for this relationship across multiple models.
     * 
     * This method is used to optimize queries by loading related data for multiple
     * models at once, preventing the N+1 query problem.
     *
     * @param string $relationName The name of the relationship being loaded
     * @param array $models Array of parent models to load the relationship for
     * @return array Array of related models indexed appropriately for matching
     */
    abstract public function eagerLoad(string $relationName, array $models): array;

    /**
     * Match eagerly loaded related models to their parent models.
     * 
     * This method takes the results from eager loading and associates them with
     * the correct parent models based on foreign/local keys.
     *
     * @param string $relationName The name of the relationship being matched
     * @param array $models Array of parent models to match against
     * @param array $related Array of related models from eager loading
     * @return array Array of parent models with their relationships populated
     */
    abstract public function match(string $relationName, array $models, array $related): array;

    /**
     * Get the underlying query builder instance.
     *
     * @return ModelQuery The query builder for this relationship
     */
    public function getQuery(): ModelQuery
    {
        return $this->query;
    }

    /**
     * Get the parent model instance.
     *
     * @return Model The parent model that owns this relationship
     */
    public function getParent(): Model
    {
        return $this->parent;
    }

    /**
     * Extract unique values for a specific key from an array of models.
     * 
     * This utility method is commonly used to collect foreign key values
     * from a collection of models for relationship queries.
     *
     * @param array $models Array of model instances
     * @param string $key The attribute/property name to extract from each model
     * @return array Array of unique values for the specified key
     */
    public function getModelKeys(array $models, string $key): array
    {
        return array_unique(array_map(fn($m) => $m->{$key}, $models));
    }

    /**
     * Dynamically delegate method calls to the underlying query builder.
     * 
     * This magic method allows relationship instances to act as proxies to their
     * query builders, enabling method chaining and query building operations.
     * If the query method returns a ModelQuery instance (chainable), this relationship
     * instance is returned to maintain fluent interface. Otherwise, the actual
     * result is returned.
     *
     * @param string $method The method name being called
     * @param array $arguments The arguments passed to the method
     * @return mixed Either this relationship instance (for chaining) or the method result
     * @throws BadMethodCallException If the method doesn't exist on the query builder
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this->query, $method)) {
            $result = $this->query->$method(...$arguments);

            // If the query returns itself (chainable), return the Relationship for further chaining
            return $result instanceof ModelQuery ? $this : $result;
        }

        throw new BadMethodCallException(
            "Method {$method} does not exist on " . static::class
        );
    }
}
