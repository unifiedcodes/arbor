<?php

namespace Arbor\database\orm\relations;

use Arbor\database\orm\Model;

class HasOne extends Relationship
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
        return $this->query->first();
    }

    public function eagerLoad(string $relationName, array $models): array
    {
        if (empty($models)) {
            return [];
        }

        // Collect all parent local keys
        $keys = $this->getModelKeys($models, $this->localKey);

        if (empty($keys)) {
            return [];
        }

        // Query children where foreignKey IN (...)
        $related = $this->relatedInstance::query()
            ->whereIn($this->foreignKey, $keys)
            ->get();

        return $this->match($relationName, $models, $related);
    }

    public function match(string $relationName, array $models, array $related): array
    {
        // Build dictionary: foreignKey => related model (first match only)
        $dictionary = [];
        foreach ($related as $rel) {
            $key = $rel->getAttribute($this->foreignKey);
            // Only keep the first one per parent
            if (!isset($dictionary[$key])) {
                $dictionary[$key] = $rel;
            }
        }

        // Attach to each parent model
        foreach ($models as $model) {
            $localValue = $model->getAttribute($this->localKey);
            $child = $dictionary[$localValue] ?? null;

            $model->setRelation(
                $relationName,
                $child
            );
        }

        return $models;
    }
}
