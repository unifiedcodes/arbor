<?php

namespace Arbor\database\orm\relations;


use Arbor\database\orm\Model;


class BelongsTo extends Relationship
{

    protected $ownerKey;
    protected $foreignKey;
    protected $foreignValue;
    protected $relatedInstance;


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


    public function resolve()
    {
        return $this->query->first();
    }


    public function eagerLoad(string $relationName, array $models)
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
