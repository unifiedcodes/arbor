<?php

namespace Arbor\database\orm\relations;


use Arbor\database\orm\Model;
use Arbor\database\query\Expression;


/**
 * MorphToMany Relationship
 *
 * Represents a polymorphic many-to-many relationship between models.
 * This relationship allows multiple model types to share a many-to-many relationship
 * through a single pivot table by using a type discriminator column.
 * For example, Posts and Videos can both have many Tags through a single taggables pivot table.
 * The morph type column in the pivot table distinguishes which model type owns each relationship.
 * 
 * @package Arbor\database\orm\relations
 * 
 */
class MorphToMany extends BelongsToMany
{
    /**
     * The column in the pivot table that stores the type discriminator.
     *
     * @var string
     */
    protected string $morphType;

    /**
     * The class name or morph alias to match against in the type column.
     * Typically the parent model's fully qualified class name.
     *
     * @var string
     */
    protected string $morphClass;


    /**
     * Constructor
     *
     * Initializes a MorphToMany relationship by setting up the polymorphic many-to-many
     * relationship between a parent model and related models through a pivot table with
     * a type discriminator column.
     *
     * @param Model $parent The parent model instance
     * @param string $related The class name of the related model
     * @param string $pivotTable The name of the pivot table
     * @param string $foreignKey The foreign key column in the pivot table referencing the parent
     * @param string $relatedKey The foreign key column in the pivot table referencing the related model
     * @param string $morphType The type discriminator column name in the pivot table
     * @param string $morphClass The value to store/match in the morph column (typically the parent's class name)
     * @param array $pivotColumns Extra columns from the pivot table to include in results
     */
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
     * Get pivot table conditions
     *
     * Builds the WHERE conditions to filter the pivot table by the parent model's primary key
     * and the morph type. This ensures only relationships of the correct model type are selected.
     *
     * @return array Array of conditions in the format [column => value]
     */
    protected function pivotConditions(): array
    {
        // Start with base parent condition (foreignKey = parentId)
        $conditions = parent::pivotConditions();

        // Add morph type condition
        $conditions["{$this->pivotTable}.{$this->morphType}"] = $this->morphClass;

        return $conditions;
    }


    /**
     * Attach related models to the pivot table
     *
     * Inserts one or more related model associations into the pivot table.
     * Automatically includes the morph type value in each row to identify the parent model type.
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

    /**
     * Detach related models from the pivot table
     *
     * Removes one or more related model associations from the pivot table.
     * Only removes relationships matching the morph type of the parent.
     * If no IDs are specified, detaches all related models for this parent of this type.
     *
     * @param int|array|null $relatedIds The ID(s) of related models to detach, or null to detach all
     *
     * @return int The number of rows deleted from the pivot table
     */
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

    /**
     * Synchronize related models in the pivot table
     *
     * Updates the pivot table to contain exactly the specified related model IDs for this morph type.
     * Detaches models not in the list and attaches new ones. Only affects relationships of the
     * parent's morph type. This is useful for updating polymorphic many-to-many relationships
     * from form submissions.
     *
     * @param int|array $relatedIds The ID(s) that should be associated with the parent model
     * @param array $extra Additional pivot table column data for newly attached records
     *
     * @return void
     */
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



    /**
     * Eager load the relationship for multiple models
     *
     * Optimizes loading of related models for a collection of parent models by executing
     * a single query with an IN clause instead of multiple queries. Filters by both the
     * parent IDs and the morph type to ensure only relationships of the correct model type
     * are loaded.
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
