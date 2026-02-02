<?php

namespace Arbor\database\orm\relations;


use Arbor\database\orm\Model;


/**
 * BelongsTo Relationship
 *
 * Represents a "belongs to" relationship between two models.
 * This relationship indicates that the child model belongs to a parent model.
 * For example, a Comment belongs to a Post, or an Order belongs to a Customer.
 * 
 * @package Arbor\database\orm\relations
 * 
 */
class BelongsTo extends Relationship
{

    /**
     * The primary key of the related (parent) model.
     *
     * @var string
     */
    protected $ownerKey;

    /**
     * The foreign key on the child model that references the parent.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The foreign key value from the parent model instance.
     *
     * @var mixed
     */
    protected $foreignValue;

    /**
     * An instance of the related (parent) model class.
     *
     * @var Model
     */
    protected $relatedInstance;


    /**
     * Constructor
     *
     * Initializes a BelongsTo relationship by setting up the relationship between
     * a child model and its parent model.
     *
     * @param Model $parent The child model instance that belongs to the parent
     * @param string $related The class name of the parent (related) model
     * @param string $foreignKey The foreign key column on the child model
     * @param string|null $ownerKey The primary key on the parent model. If null, uses the parent's primary key
     */
    public function __construct(Model $parent, string $related, string $foreignKey, ?string $ownerKey = null)
    {
        $this->relatedInstance = new $related;
        $this->ownerKey = $ownerKey ?? $this->relatedInstance->getPrimaryKey();
        $this->foreignKey = $foreignKey;
        $this->foreignValue = $parent->getAttribute($this->foreignKey);

        // Build the query
        $query = $related::query()->where($this->ownerKey, $this->foreignValue);

        parent::__construct($parent, $query);
    }


    /**
     * Resolve the relationship
     *
     * Executes the query and returns the first (and typically only) related parent model.
     *
     * @return Model|null The parent model instance, or null if not found
     */
    public function resolve()
    {
        return $this->query->first();
    }


    /**
     * Eager load the relationship for multiple models
     *
     * Optimizes loading of parent models for a collection of child models by executing
     * a single query with an IN clause instead of multiple queries.
     *
     * @param string $relationName The name of the relationship to attach to the models
     * @param array $models Array of child model instances
     *
     * @return array The models array with the relationship data attached
     */
    public function eagerLoad(string $relationName, array $models): array
    {
        if (empty($models)) {
            return [];
        }

        $keys = $this->getModelKeys($models, $this->foreignKey);

        if (empty($keys)) {
            return [];
        }

        // Step 2: Query all parents where ownerKey IN (...)
        $related = $this->relatedInstance::query()->whereIn($this->ownerKey, $keys)->get();

        return $this->match($relationName, $models, $related);
    }


    /**
     * Match related models to child models
     *
     * Associates each child model with its corresponding parent model by matching
     * foreign key values to the parent's primary key values.
     *
     * @param string $relationName The name of the relationship to attach
     * @param array $models Array of child model instances
     * @param array $related Array of parent model instances
     *
     * @return array The models array with the relationship data attached to each model
     */
    public function match(string $relationName, array $models, array $related): array
    {
        // Build dictionary: ownerKey => related model
        $dictionary = [];
        foreach ($related as $rel) {
            $dictionary[$rel->getAttribute($this->ownerKey)] = $rel;
        }

        // Attach to each child model
        foreach ($models as $model) {
            $foreignValue = $model->getAttribute($this->foreignKey);
            $owner = $dictionary[$foreignValue] ?? null;

            $model->setRelation(
                $relationName,
                $owner
            );
        }

        return $models;
    }
}
