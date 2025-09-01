<?php

namespace Arbor\database\orm\relations;


use Arbor\database\orm\Model;


class HasOne extends Relationship
{
    public function __construct(Model $parent, string $related, string $foreignKey, ?string $localKey = null)
    {
        $localKey = $localKey ?? $parent->getPrimaryKey();

        $query = $related::query()->where($foreignKey, $parent->getAttribute($localKey));

        parent::__construct($parent, $query);
    }

    public function resolve()
    {
        return $this->query->first();
    }
}
