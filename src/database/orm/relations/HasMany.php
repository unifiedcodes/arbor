<?php

namespace Arbor\database\orm\relations;

use Arbor\database\orm\Model;

/**
 * HasMany Relationship
 *
 * Represents a one-to-many relationship between two models.
 * This relationship indicates that the parent model has many related child models.
 * For example, a Post has many Comments, or a User has many Orders.
 * 
 * @package Arbor\database\orm\relations
 * 
 */
class HasMany extends Relationship
{
    /**
     * The foreign key on the related model that references the parent.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The local key on the parent model that is referenced by the foreign key.
     *
     * @var string
     */
    protected $localKey;

    /**
     * An instance of the related model class.
     *
     * @var Model
     */
    protected $relatedInstance;

    /**
     * Constructor
     *
     * Initializes a HasMany relationship by setting up the relationship between
     * a parent model and its many child models.
     *
     * @param Model $parent The parent model instance
     * @param string $related The class name of the related model
     * @param string $foreignKey The foreign key column on the related model that references the parent
     * @param string|null $localKey The primary key on the parent model. If null, uses the parent's primary key
     */
    public function __construct(Model $parent, string $related, string $foreignKey, ?string $localKey = null)
    {
        $this->relatedInstance = new $related;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey ?? $parent->getPrimaryKey();

        $query = $related::query()->where($this->foreignKey, $parent->getAttribute($this->localKey));

        parent::__construct($parent, $query);
    }


    /**
     * Resolve the relationship
     *
     * Executes the query and returns all related child models.
     *
     * @return array Array of related model instances
     */
    public function resolve()
    {
        return $this->query->get();
    }


    /**
     * Eager load the relationship for multiple models
     *
     * Optimizes loading of child models for a collection of parent models by executing
     * a single query with an IN clause instead of multiple queries.
     *
     * @param string $relationName The name of the relationship to attach to the models
     * @param array $models Array of parent model instances
     *
     * @return array The models array with the relationship data attached
     */
    public function eagerLoad(string $relationName, array $models): array
    {
        if (empty($models)) {
            return [];
        }

        // Gather all local keys from parent models
        $keys = $this->getModelKeys($models, $this->localKey);

        if (empty($keys)) {
            return [];
        }

        // Fetch related models where foreignKey IN (...)
        $related = $this->relatedInstance::query()
            ->whereIn($this->foreignKey, $keys)
            ->get();

        return $this->match($relationName, $models, $related);
    }


    /**
     * Match related models to parent models
     *
     * Associates each parent model with its corresponding child models by grouping
     * related models by their foreign key values.
     *
     * @param string $relationName The name of the relationship to attach
     * @param array $models Array of parent model instances
     * @param array $related Array of related model instances
     *
     * @return array The models array with the relationship data attached to each model
     */
    public function match(string $relationName, array $models, array $related): array
    {
        // Build dictionary: foreignKey => [related models]
        $dictionary = [];
        foreach ($related as $rel) {
            $key = $rel->getAttribute($this->foreignKey);
            $dictionary[$key][] = $rel;
        }

        // Attach to each parent model
        foreach ($models as $model) {
            $localValue = $model->getAttribute($this->localKey);
            $children = $dictionary[$localValue] ?? [];

            $model->setRelation(
                $relationName,
                $children
            );
        }

        return $models;
    }
}
