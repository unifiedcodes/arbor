<?php

namespace Arbor\database\query\helpers;

use Arbor\database\query\Builder;
use Arbor\database\query\Expression;
use Closure;

/**
 * Trait providing general Helper methods for the query builder.
 */
trait HelpersTrait
{

    // alias of limit
    public function take(int $value): static
    {
        return $this->limit($value);
    }

    // alias of offset
    public function skip(int $value): static
    {
        return $this->offset($value);
    }


    // helper method for order
    public function orderByField(string $column, string|array $values): static
    {
        if (is_array($values)) {
            // Quote or escape manually if needed by Grammar later
            $valueList = implode(', ', array_map(fn($v) => "'$v'", $values));
        } else {
            $valueList = "'$values'";
        }

        $expr = new Expression("FIELD($column, $valueList)");

        return $this->orderBy($expr);
    }


    // helper method for recursive with
    public function withRecursive(string $alias, Closure|Builder $query): static
    {
        return $this->with($alias, $query, true);
    }


    // helper insert method when using subqueries : rare usage
    public function insertUsing(array $columns, Builder|Expression $subQuery): static
    {
        return $this->addInsert(
            array_fill_keys($columns, null),
            $subQuery
        );
    }

    // fancy helper delete wrapper, to make delete on multiple table more verbose.
    public function deleteMultiple(array $tableAliases): static
    {
        return $this->delete(implode(',', $tableAliases));
    }

}
