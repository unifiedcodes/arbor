<?php

namespace Arbor\database\orm\relations;


use Arbor\database\orm\Model;
use Arbor\database\orm\ModelQuery;
use Arbor\database\query\Expression;
use Arbor\database\orm\Junction;


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
        array $pivotColumns    // Extra columns in pivot table.
    ) {
        $this->related = $related;
        $this->pivotTable = $pivotTable;
        $this->foreignKey = $foreignKey;
        $this->relatedKey = $relatedKey;
        $this->pivotColumns  = $pivotColumns;

        $this->parentKey = $parent::getPrimaryKey();
        $this->relatedPrimary = $related::getPrimaryKey();


        $query = $this->makeJoin($parent);

        parent::__construct($parent, $query);
    }


    protected function makeJoin($parent): ModelQuery
    {
        $relatedTable = $this->related::getTableName();
        $pivot = $this->pivotTable;

        $parentId = $parent->getAttribute($this->parentKey);

        $selects = [new Expression("{$relatedTable}.*")];

        // auto-alias pivot cols
        $pivotCols = array_merge(
            $this->pivotColumns,
            [$this->foreignKey, $this->relatedKey]
        );

        foreach ($pivotCols as $col) {
            $selects[] = new Expression("{$pivot}.{$col} AS pivot_{$col}");
        }

        return $this->related::query()
            ->select($selects)
            ->join(
                $pivot,
                "{$relatedTable}.{$this->relatedPrimary}",
                new Expression("{$pivot}.{$this->relatedKey}")
            )
            ->where("{$pivot}.{$this->foreignKey}", $parentId);
    }


    public function resolve()
    {
        $records = $this->query->fetchAll();
        $models = [];

        foreach ($records as $record) {
            // Separate pivot and related attributes
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
            $pivot = new Junction($this->pivotTable);
            $pivot->fill($pivotData);
            $pivot->exists(true);

            // Attach pivot as a relation
            $related->setRelation('pivot', $pivot);

            $models[] = $related;
        }

        return $models;
    }
}
