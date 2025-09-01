<?php

namespace Arbor\database\orm\relations;


use Arbor\database\orm\Model;


class BelongsTo extends Relationship
{
    public function __construct(Model $parent, string $related, string $foreignKey, ?string $ownerKey = null)
    {
        $relatedInstance = new $related;
        $ownerKey = $ownerKey ?? $relatedInstance->getPrimaryKey();
        $foreignValue = $parent->getAttribute($foreignKey);

        // Build the query
        $query = $related::query()->where($ownerKey, $foreignValue);

        parent::__construct($parent, $query);
    }

    public function resolve()
    {
        return $this->query->first();
    }
}
