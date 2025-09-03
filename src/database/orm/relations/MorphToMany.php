<?php

namespace Arbor\database\orm\relations;


use Arbor\database\orm\Model;


/**
 * MorphToMany: many-to-many relationship with a type discriminator.
 * Example: posts <-> tags through taggables
 */
class MorphToMany extends BelongsToMany
{
    protected string $morphType;   // column in pivot table that stores type
    protected string $morphClass;  // class name (or morph alias) to match against


    public function __construct(
        Model $parent,
        string $related,
        string $pivotTable,
        string $foreignKey,     // FK in pivot referencing parent
        string $relatedKey,     // FK in pivot referencing related
        string $morphType,      // pivot column that stores type
        string $morphClass,     // value to store in morph column (e.g. Post::class)
        array $pivotColumns = [] // extra pivot columns
    ) {
        $this->morphType  = $morphType;
        $this->morphClass = $morphClass;

        parent::__construct(
            $parent,
            $related,
            $pivotTable,
            $foreignKey,
            $relatedKey,
            $pivotColumns
        );
    }

    /**
     * Add morph type condition on top of parent pivotConditions.
     */
    protected function pivotConditions(): array
    {
        // Start with base parent condition (foreignKey = parentId)
        $conditions = parent::pivotConditions();

        // Add morph type condition
        $conditions["{$this->pivotTable}.{$this->morphType}"] = $this->morphClass;

        return $conditions;
    }
}
