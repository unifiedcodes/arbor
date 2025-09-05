<?php

namespace Arbor\database\orm\relations;


use Arbor\database\orm\Model;
use Arbor\database\orm\ModelQuery;
use Arbor\database\query\Expression;
use Arbor\database\orm\Pivot;


class BelongsToMany extends Relationship
{
    protected string $related;
    protected string $pivotTable;
    protected string $foreignKey;
    protected string $relatedKey;
    protected string $parentKey;
    protected string $relatedPrimary;
    protected array  $pivotColumns;


    public function __construct(
        Model $parent,
        string $related,
        string $pivotTable,
        string $foreignKey,     // FK in pivot referencing parent
        string $relatedKey,     // FK in pivot referencing related
        array $pivotColumns     // Extra columns in pivot table.
    ) {
        $this->related = $related;
        $this->pivotTable = $pivotTable;
        $this->foreignKey = $foreignKey;
        $this->relatedKey = $relatedKey;
        $this->pivotColumns  = $pivotColumns;

        $this->parentKey = $parent::getPrimaryKey();
        $this->relatedPrimary = $related::getPrimaryKey();

        $this->parent = $parent;

        $query = $this->makeJoin();

        parent::__construct($parent, $query);
    }


    protected function pivotConditions(): array
    {
        return [
            "{$this->pivotTable}.{$this->foreignKey}" => $this->parent->getAttribute($this->parentKey)
        ];
    }


    protected function getSelectColumns(array $columns = []): array
    {
        $relatedTable = $this->related::getTableName();


        if (empty($columns)) {
            $columns = [
                new Expression("{$relatedTable}.*")
            ];
        }

        // auto-alias pivot cols
        $pivotCols = array_merge(
            $this->pivotColumns,
            [$this->foreignKey, $this->relatedKey]
        );


        foreach ($pivotCols as $col) {
            $columns[] = new Expression("{$this->pivotTable}.{$col} AS pivot_{$col}");
        }

        return $columns;
    }


    protected function makeJoin(): ModelQuery
    {
        $relatedTable = $this->related::getTableName();
        $pivot = $this->pivotTable;


        $columns = $this->getSelectColumns();

        return $this->related::query()
            ->select($columns)
            ->join(
                $pivot,
                "{$relatedTable}.{$this->relatedPrimary}",
                new Expression("{$pivot}.{$this->relatedKey}")
            )
            ->where($this->pivotConditions());
    }


    protected function hydrateWithPivot(array $record): Model
    {
        $pivotData = [];
        $relatedData = [];

        foreach ($record as $key => $value) {
            if (str_starts_with($key, 'pivot_')) {
                $pivotData[substr($key, 6)] = $value; // remove "pivot_"
            } else {
                $relatedData[$key] = $value;
            }
        }

        // Hydrate related model (e.g., Users)
        $related = $this->related::hydrate($relatedData);

        // Hydrate pivot model
        $pivot = new Pivot(
            $this->pivotTable,
            $this->parent::getTableName(),
            $this->related::getTableName(),
            $this->parentKey,
            $this->relatedKey,
            $this->pivotColumns
        );

        $pivot->fill($pivotData);
        $pivot->exists(true);

        // Attach pivot as a relation
        $related->setRelation('pivot', $pivot);

        return $related;
    }


    public function resolve()
    {
        $records = $this->query->fetchAll();
        $models = [];

        foreach ($records as $record) {
            $models[] = $this->hydrateWithPivot($record);
        }

        return $models;
    }


    public function detach($relatedIds = null): int
    {
        $pivotTable = $this->pivotTable;
        $relatedKey = $this->relatedKey;

        // spawn a new builder
        $builder = $this->parent::getDatabase()->table($pivotTable);

        // Apply base pivot conditions
        $builder->where($this->pivotConditions());

        // If IDs specified, also constrain on relatedKey
        if (!is_null($relatedIds)) {
            $ids = is_array($relatedIds) ? $relatedIds : [$relatedIds];
            $builder->whereIn($relatedKey, $ids);
        }

        // Execute delete
        return $builder->delete();
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
                    $foreignKey => $parentId,
                    $relatedKey => $id,
                ],
                $extra
            );
        }

        // spawn a new builder for the pivot table
        $builder = $this->parent::getDatabase()->table($pivotTable);
        $builder->insertMany($rows);
    }


    public function sync($relatedIds, array $extra = []): void
    {
        $pivotTable = $this->pivotTable;
        $relatedKey = $this->relatedKey;

        $ids = is_array($relatedIds) ? $relatedIds : [$relatedIds];

        // Get current related IDs from pivot
        $builder = $this->parent::getDatabase()->table($pivotTable);

        $currentIds = $builder->where($this->pivotConditions())
            ->pluck($relatedKey); // assume pluck() returns a flat array

        // Determine what to detach and what to attach
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

        // Columns: related.* + pivot columns
        $columns = $this->getSelectColumns();

        // Fetch related models joined with pivot, constrained by parent IDs
        $records = $this->related::query()
            ->select($columns)
            ->join(
                $pivot,
                "{$relatedTable}.{$this->relatedPrimary}",
                new Expression("{$pivot}.{$this->relatedKey}")
            )
            ->whereIn("{$pivot}.{$this->foreignKey}", $keys)
            ->fetchAll();

        $relatedModels = [];
        foreach ($records as $record) {
            $relatedModels[] = $this->hydrateWithPivot($record);
        }

        return $this->match($relationName, $models, $relatedModels);
    }


    public function match(string $relationName, array $models, array $related): array
    {
        // Build dictionary: parent_id => [related models]
        $dictionary = [];

        foreach ($related as $rel) {
            /** @var Pivot $pivot */
            $pivot = $rel->getRelation('pivot');
            $parentId = $pivot->getAttribute($this->foreignKey);
            $dictionary[$parentId][] = $rel;
        }

        // Attach related models to each parent
        foreach ($models as $model) {
            $parentId = $model->getAttribute($this->parentKey);
            $children = $dictionary[$parentId] ?? [];

            $model->setRelation(
                $relationName,
                $children
            );
        }

        return $models;
    }
}
