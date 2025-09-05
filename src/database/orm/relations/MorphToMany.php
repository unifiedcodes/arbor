<?php

namespace Arbor\database\orm\relations;


use Arbor\database\orm\Model;
use Arbor\database\query\Expression;


/**
 * 
 * MorphToMany: many-to-many relationship with a type discriminator.
 * Example: posts <-> tags through taggables
 * 
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


    public function attach($relatedIds, array $extra = []): void
    {
        $pivotTable = $this->pivotTable;
        $foreignKey = $this->foreignKey;
        $relatedKey = $this->relatedKey;
        $parentId   = $this->parent->getAttribute($this->parentKey);

        $ids = is_array($relatedIds) ? $relatedIds : [$relatedIds];

        $rows = [];
        foreach ($ids as $id) {
            $rows[] = array_merge(
                [
                    $foreignKey       => $parentId,
                    $relatedKey       => $id,
                    $this->morphType  => $this->morphClass,
                ],
                $extra
            );
        }

        $builder = $this->parent::getDatabase()->table($pivotTable);
        $builder->insertMany($rows);
    }

    public function detach($relatedIds = null): int
    {
        $builder = $this->parent::getDatabase()->table($this->pivotTable);

        // Apply base + morph conditions
        $builder->where($this->pivotConditions());

        if (!is_null($relatedIds)) {
            $ids = is_array($relatedIds) ? $relatedIds : [$relatedIds];
            $builder->whereIn($this->relatedKey, $ids);
        }

        return $builder->delete();
    }

    public function sync($relatedIds, array $extra = []): void
    {
        $ids = is_array($relatedIds) ? $relatedIds : [$relatedIds];

        $builder = $this->parent::getDatabase()->table($this->pivotTable);

        // Only current morph relations
        $currentIds = $builder->where($this->pivotConditions())
            ->pluck($this->relatedKey);

        $toDetach = array_diff($currentIds, $ids);
        $toAttach = array_diff($ids, $currentIds);

        if (!empty($toDetach)) {
            $this->detach($toDetach);
        }

        if (!empty($toAttach)) {
            $this->attach($toAttach, $extra);
        }
    }



    public function eagerLoad(string $relationName, array $models): array
    {
        if (empty($models)) {
            return [];
        }

        // Collect parent IDs
        $keys = $this->getModelKeys($models, $this->parentKey);

        if (empty($keys)) {
            return [];
        }

        $relatedTable = $this->related::getTableName();
        $pivot = $this->pivotTable;

        $columns = $this->getSelectColumns();

        // Fetch joined records with morph condition
        $records = $this->related::query()
            ->select($columns)
            ->join(
                $pivot,
                "{$relatedTable}.{$this->relatedPrimary}",
                new Expression("{$pivot}.{$this->relatedKey}")
            )
            ->whereIn("{$pivot}.{$this->foreignKey}", $keys)
            ->where("{$pivot}.{$this->morphType}", $this->morphClass)
            ->fetchAll();

        $relatedModels = [];
        foreach ($records as $record) {
            $relatedModels[] = $this->hydrateWithPivot($record);
        }

        return $this->match($relationName, $models, $relatedModels);
    }
}
