<?php

namespace Arbor\database\orm\relations;

use Arbor\database\orm\Model;

class HasMany extends Relationship
{
    protected $foreignKey;
    protected $localKey;
    protected $relatedInstance;

    public function __construct(Model $parent, string $related, string $foreignKey, ?string $localKey = null)
    {
        $this->relatedInstance = new $related;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey ?? $parent->getPrimaryKey();

        $query = $related::query()->where($this->foreignKey, $parent->getAttribute($this->localKey));

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
