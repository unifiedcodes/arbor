<?php

namespace Arbor\database\orm\relations;


use Arbor\database\orm\Model;
use Arbor\database\orm\ModelQuery;
use Arbor\database\query\Expression;
use Arbor\database\orm\Pivot;


/**
 * BelongsToMany Relationship
 *
 * Represents a many-to-many relationship between two models through a pivot table.
 * For example, a Student belongs to many Courses, and a Course has many Students.
 * The pivot table stores the relationship between the two models along with any extra metadata.
 * 
 * @package Arbor\database\orm\relations
 * 
 */
class BelongsToMany extends Relationship
{
    /**
     * The related model class name.
     *
     * @var string
     */
    protected string $related;

    /**
     * The name of the pivot table connecting the two models.
     *
     * @var string
     */
    protected string $pivotTable;

    /**
     * The foreign key in the pivot table referencing the parent model.
     *
     * @var string
     */
    protected string $foreignKey;

    /**
     * The foreign key in the pivot table referencing the related model.
     *
     * @var string
     */
    protected string $relatedKey;

    /**
     * The primary key of the parent model.
     *
     * @var string
     */
    protected string $parentKey;

    /**
     * The primary key of the related model.
     *
     * @var string
     */
    protected string $relatedPrimary;

    /**
     * Extra columns in the pivot table to be included in the results.
     *
     * @var array
     */
    protected array  $pivotColumns;


    /**
     * Constructor
     *
     * Initializes a BelongsToMany relationship by setting up the join query
     * between the parent model and related model through a pivot table.
     *
     * @param Model $parent The parent model instance
     * @param string $related The class name of the related model
     * @param string $pivotTable The name of the pivot table
     * @param string $foreignKey The foreign key column in the pivot table referencing the parent
     * @param string $relatedKey The foreign key column in the pivot table referencing the related model
     * @param array $pivotColumns Extra columns from the pivot table to include in results
     */
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

        parent::__construct($parent);
    }


    protected function newQuery(): ModelQuery
    {
        return $this->makeJoin();
    }

    /**
     * Get pivot table conditions
     *
     * Builds the WHERE conditions to filter the pivot table by the parent model's primary key.
     *
     * @return array Array of conditions in the format [column => value]
     */
    protected function pivotConditions(): array
    {
        return [
            "{$this->pivotTable}.{$this->foreignKey}" => $this->parent->getAttribute($this->parentKey)
        ];
    }


    /**
     * Get columns to select in the query
     *
     * Builds the list of columns to select, including all related model columns
     * and pivot table columns aliased with "pivot_" prefix.
     *
     * @param array $columns Optional array of columns to select. If empty, selects all related columns
     *
     * @return array Array of column selections including Expression objects for aliasing
     */
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


    /**
     * Build the join query
     *
     * Constructs the query that joins the related model table with the pivot table
     * and applies the pivot conditions to filter results.
     *
     * @return ModelQuery The constructed query with joins and conditions
     */
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


    /**
     * Hydrate record with pivot data
     *
     * Separates pivot data from related model data and creates both a related model
     * instance and a Pivot instance with the separated data.
     *
     * @param array $record The raw database record containing both related and pivot columns
     *
     * @return Model The related model instance with the pivot attached as a relation
     */
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


    /**
     * Resolve the relationship
     *
     * Executes the query and returns all related models hydrated with their pivot data.
     *
     * @return array Array of related model instances with pivot data attached
     */
    public function resolve()
    {
        $records = $this->newQuery()->fetchAll();
        $models = [];

        foreach ($records as $record) {
            $models[] = $this->hydrateWithPivot($record);
        }

        return $models;
    }


    /**
     * Detach related models from the pivot table
     *
     * Removes one or more related model associations from the pivot table.
     * If no IDs are specified, detaches all related models for this parent.
     *
     * @param int|array|null $relatedIds The ID(s) of related models to detach, or null to detach all
     *
     * @return int The number of rows deleted from the pivot table
     */
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


    /**
     * Attach related models to the pivot table
     *
     * Inserts one or more related model associations into the pivot table.
     * Extra pivot data can be provided for additional columns.
     *
     * @param int|array $relatedIds The ID(s) of related models to attach
     * @param array $extra Additional pivot table column data to insert (e.g., ['role' => 'admin'])
     *
     * @return void
     */
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


    /**
     * Synchronize related models in the pivot table
     *
     * Updates the pivot table to contain exactly the specified related model IDs.
     * Detaches models not in the list and attaches new ones. This is useful for
     * updating many-to-many relationships from form submissions.
     *
     * @param int|array $relatedIds The ID(s) that should be associated with the parent model
     * @param array $extra Additional pivot table column data for newly attached records
     *
     * @return void
     */
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


    /**
     * Eager load the relationship for multiple models
     *
     * Optimizes loading of related models for a collection of parent models by executing
     * a single query with an IN clause instead of multiple queries.
     *
     * @param string $relationName The name of the relationship to attach to the models
     * @param array $models Array of parent model instances
     *
     * @return array The models array with the relationship data attached
     */
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


    /**
     * Match related models to parent models
     *
     * Associates each parent model with its corresponding related models by grouping
     * related models by their parent ID from the pivot data.
     *
     * @param string $relationName The name of the relationship to attach
     * @param array $models Array of parent model instances
     * @param array $related Array of related model instances with pivot data
     *
     * @return array The models array with the relationship data attached to each model
     */
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
