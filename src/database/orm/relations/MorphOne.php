<?php

namespace Arbor\database\orm\relations;

use Arbor\database\orm\Model;

class MorphOne extends Relationship
{
    public function __construct(Model $parent, string $related, string $foreignKey, string $typeKey)
    {
        $parentId   = $parent->getPrimaryKeyValue();
        $parentType = static::class;

        $query = $related::query()
            ->where($foreignKey, $parentId)
            ->where($typeKey, $parentType);

        parent::__construct($parent, $query);
    }


    public function resolve()
    {
        return $this->query->first();
    }
}
