<?php

namespace Arbor\database\orm\relations;

use Arbor\database\orm\Model;

class MorphOne extends Relationship
{

    protected $foreignKey;
    protected $typeKey;
    protected $localKey;
    protected $relatedInstance;
    protected $morphType;


    public function __construct(Model $parent, string $related, string $foreignKey, string $typeKey, ?string $localKey = null)
    {
        $this->relatedInstance = new $related;
        $this->foreignKey = $foreignKey;
        $this->typeKey = $typeKey;
        $this->localKey = $localKey ?? $parent->getPrimaryKey();
        $this->morphType = get_class($parent);

        $query = $related::query()
            ->where($this->foreignKey, $parent->getAttribute($this->localKey))
            ->where($this->typeKey, $this->morphType);

        parent::__construct($parent, $query);
    }


    public function resolve()
    {
        return $this->query->first();
    }


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
