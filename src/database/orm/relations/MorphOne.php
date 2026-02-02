<?php

namespace Arbor\database\orm\relations;

use Arbor\database\orm\Model;
use Arbor\database\orm\ModelQuery;

/**
 * MorphOne Relationship
 *
 * Represents a polymorphic one-to-one relationship between models.
 * This relationship allows a model to have one related model of different types through a single table.
 * For example, a User and a Company can both have one Avatar through a single avatars table.
 * The type key column stores which model type owns the child record.
 * 
 * @package Arbor\database\orm\relations
 * 
 * 
 */
class MorphOne extends Relationship
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
     * An instance of the related model class.
     *
     * @var Model
     */
    protected $relatedInstance;

    /**
     * The morph type (class name) of the parent model.
     *
     * @var string
     */
    protected $morphType;


    /**
     * Constructor
     *
     * Initializes a MorphOne relationship by setting up the polymorphic relationship
     * between a parent model and a single related child model of the same type.
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
     * Executes the query and returns the first (and typically only) related child model
     * of the parent's morph type.
     *
     * @return Model|null The related model instance, or null if not found
     */
    public function resolve()
    {
        return $this->newQuery()->first();
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

        // Collect parent local keys
        $keys = $this->getModelKeys($models, $this->localKey);

        if (empty($keys)) {
            return [];
        }

        // Query related models where foreignKey IN (...) and type matches
        $related = $this->relatedInstance::query()
            ->whereIn($this->foreignKey, $keys)
            ->where($this->typeKey, $this->morphType)
            ->get();

        return $this->match($relationName, $models, $related);
    }


    /**
     * Match related model to parent models
     *
     * Associates each parent model with its corresponding single polymorphic child model by mapping
     * related models by their foreign key values. Only keeps the first related model per parent
     * to maintain the one-to-one relationship.
     *
     * @param string $relationName The name of the relationship to attach
     * @param array $models Array of parent model instances
     * @param array $related Array of related model instances of the same morph type
     *
     * @return array The models array with the relationship data attached to each model
     */
    public function match(string $relationName, array $models, array $related): array
    {
        $dictionary = [];
        foreach ($related as $rel) {
            $key = $rel->getAttribute($this->foreignKey);
            $dictionary[$key] = $rel; // single instance, not array
        }

        foreach ($models as $model) {
            $localValue = $model->getAttribute($this->localKey);
            $child = $dictionary[$localValue] ?? null;

            $model->setRelation($relationName, $child);
        }

        return $models;
    }
}
