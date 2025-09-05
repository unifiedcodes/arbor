<?php

namespace Arbor\database\orm\relations;

use Arbor\database\orm\Model;

class MorphMany extends Relationship
{
    protected $foreignKey;
    protected $typeKey;
    protected $localKey;
    protected $morphType;
    protected $relatedInstance;

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
        return $this->query->get();
    }

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
