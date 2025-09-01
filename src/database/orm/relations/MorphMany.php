<?php

namespace Arbor\database\orm\relations;

use Arbor\database\orm\Model;

class MorphMany extends Relationship
{
    public function __construct(Model $parent, string $related, string $foreignKey, string $typeKey)
    {
        $parentId   = $parent->getPrimaryKeyValue();
        $parentType = get_class($parent); // actual class name of parent

        $query = $related::query()
            ->where($foreignKey, $parentId)
            ->where($typeKey, $parentType);

        parent::__construct($parent, $query);
    }


    public function resolve()
    {
        return $this->query->get();
    }
}
