<?php

namespace Arbor\database\orm\relations;

use Arbor\database\orm\Model;
use Arbor\database\orm\ModelQuery;

/**
 * MorphMany Relationship
 *
 * Represents a polymorphic one-to-many relationship between models.
 * This relationship allows a model to have many related models of different types through a single table.
 * For example, a Post and a Video can both have many Comments through a single comments table.
 * The type key column stores which model type owns each child record.
 * 
 * @package Arbor\database\orm\relations
 * 
 * 
 */
class MorphMany extends Relationship
{
    /**
     * The foreign key on the related model that references the parent's local key.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The type key column that stores the class name or type identifier of the parent model.
     *
     * @var string
     */
    protected $typeKey;

    /**
     * The local key on the parent model that is referenced by the foreign key.
     *
     * @var string
     */
    protected $localKey;

    /**
     * The morph type (class name) of the parent model.
     *
     * @var string
     */
    protected $morphType;

    /**
     * An instance of the related model class.
     *
     * @var Model
     */
    protected $relatedInstance;

    /**
     * Constructor
     *
     * Initializes a MorphMany relationship by setting up the polymorphic relationship
     * between a parent model and many related child models of the same type.
     *
     * @param Model $parent The parent model instance
     * @param string $related The class name of the related model
     * @param string $foreignKey The foreign key column on the related model that references the parent
     * @param string $typeKey The type key column that stores the parent model's class name
     * @param string|null $localKey The primary key on the parent model. If null, uses the parent's primary key
     */
    public function __construct(Model $parent, string $related, string $foreignKey, string $typeKey, ?string $localKey = null)
    {
        $this->relatedInstance = new $related;
        $this->foreignKey = $foreignKey;
        $this->typeKey = $typeKey;
        $this->localKey = $localKey ?? $parent->getPrimaryKey();
        $this->morphType = get_class($parent);

        parent::__construct($parent);
    }

    protected function newQuery(): ModelQuery
    {
        return $this->relatedInstance::query()
            ->where(
                $this->foreignKey,
                $this->parent->getAttribute($this->localKey)
            )
            ->where(
                $this->typeKey,
                $this->morphType
            );
    }

    /**
     * Resolve the relationship
     *
     * Executes the query and returns all related child models of the parent's morph type.
     *
     * @return array Array of related model instances
     */
    public function resolve()
    {
        return $this->newQuery()->get();
    }

    /**
     * Eager load the relationship for multiple models
     *
     * Optimizes loading of polymorphic child models for a collection of parent models by executing
     * a single query with an IN clause instead of multiple queries. Filters by both foreign key
     * and morph type to ensure only models of the correct type are loaded.
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

        // Collect all local keys from parent models
        $keys = $this->getModelKeys($models, $this->localKey);

        if (empty($keys)) {
            return [];
        }

        // Fetch related models where foreignKey IN (...) and typeKey = morphType
        $related = $this->relatedInstance::query()
            ->whereIn($this->foreignKey, $keys)
            ->where($this->typeKey, $this->morphType)
            ->get();

        return $this->match($relationName, $models, $related);
    }

    /**
     * Match related models to parent models
     *
     * Associates each parent model with its corresponding polymorphic child models by grouping
     * related models by their foreign key values. All related models in this collection will
     * have the same morph type as the parent.
     *
     * @param string $relationName The name of the relationship to attach
     * @param array $models Array of parent model instances
     * @param array $related Array of related model instances of the same morph type
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

        // Attach related children to each parent model
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
