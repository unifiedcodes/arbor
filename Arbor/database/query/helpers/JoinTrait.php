<?php

namespace Arbor\database\query\helpers;

use Arbor\database\query\Builder;
use Arbor\database\query\Expression;
use Closure;

/**
 * Trait providing JOIN clause helper methods for the query builder.
 */
trait JoinTrait
{
    /**
     * Add a ON clause to the most recent join.
     */
    public function on(
        Expression|Closure|Builder|string|array $left,
        mixed $right = null,
        string $operator = '=',
        string $boolean = 'AND'
    ): static {
        if (empty($this->joins)) {
            throw new \InvalidArgumentException('Cannot add ON clause without a JOIN');
        }

        $condition = $this->buildCondition(
            'join',
            $left,
            $right,
            $operator,
            $boolean,
            false,
            'basic'
        );

        // If the join already has conditions, append to them
        $key = array_key_last($this->joins);

        $this->joins[$key]['on'][] = $condition;

        return $this;
    }

    /**
     * Add an OR condition to the most recent join's ON clause.
     */
    public function orOn(
        Expression|Closure|Builder|string|array $left,
        mixed $right = null,
        string $operator = '='
    ): static {
        return $this->on($left, $right, $operator, 'OR');
    }

    /**
     * Add an INNER JOIN clause.
     */
    public function join(
        string|Closure|Builder|array $table,
        Expression|Closure|Builder|string|array $left,
        mixed $right = null,
        string $operator = '=',
        ?string $alias = null
    ): static {
        return $this->addJoin($table, $left, $right, $operator, 'inner', $alias);
    }

    /**
     * Add a LEFT JOIN clause.
     */
    public function leftJoin(
        string|Closure|Builder|array $table,
        Expression|Closure|Builder|string|array $left,
        mixed $right = null,
        string $operator = '=',
        ?string $alias = null
    ): static {
        return $this->addJoin($table, $left, $right, $operator, 'left', $alias);
    }

    /**
     * Add a RIGHT JOIN clause.
     */
    public function rightJoin(
        string|Closure|Builder|array $table,
        Expression|Closure|Builder|string|array $left,
        mixed $right = null,
        string $operator = '=',
        ?string $alias = null
    ): static {
        return $this->addJoin($table, $left, $right, $operator, 'right', $alias);
    }

    /**
     * Add a CROSS JOIN clause.
     */
    public function crossJoin(
        string|Closure|Builder|array $table,
        ?string $alias = null
    ): static {
        ['table' => $table, 'alias' => $alias] = $this->parseTable($table, $alias);

        $this->joins[] = [
            'type'  => 'cross',
            'table' => $table,
            'alias' => $alias,
            'on'    => [], // Cross joins don't have ON conditions
        ];

        return $this;
    }

    /**
     * Add a FULL OUTER JOIN clause.
     */
    public function fullJoin(
        string|Closure|Builder|array $table,
        Expression|Closure|Builder|string|array $left,
        mixed $right = null,
        string $operator = '=',
        ?string $alias = null
    ): static {
        return $this->addJoin($table, $left, $right, $operator, 'full', $alias);
    }

    /**
     * Add a JOIN with a raw ON clause.
     */
    public function joinRaw(
        string $table,
        string $condition,
        array $bindings = [],
        string $type = 'inner',
        ?string $alias = null
    ): static {
        // Add bindings to join section
        foreach ($bindings as $binding) {
            $this->bindings['join'][] = $binding;
        }

        ['table' => $table, 'alias' => $alias] = $this->parseTable($table, $alias);

        $this->joins[] = [
            'type'  => $type,
            'table' => $table,
            'alias' => $alias,
            'on'    => [[
                'type'     => 'raw',
                'left'     => new Expression($condition),
                'operator' => null,
                'right'    => null,
                'boolean'  => 'AND',
                'negate'   => false,
            ]],
        ];

        return $this;
    }

    /**
     * Add a NATURAL JOIN clause.
     */
    public function naturalJoin(string|array $table, ?string $alias = null): static
    {
        ['table' => $table, 'alias' => $alias] = $this->parseTable($table, $alias);

        $this->joins[] = [
            'type'  => 'natural',
            'table' => $table,
            'alias' => $alias,
            'on'    => [], // Natural joins don't have ON conditions
        ];

        return $this;
    }
}
