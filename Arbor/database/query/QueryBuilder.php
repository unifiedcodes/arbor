<?php

namespace Arbor\database\query;


class QueryBuilder
{
    protected $type = null;      // SELECT, INSERT, UPDATE, DELETE
    protected $tables = [];
    protected $columns = [];
    protected $wheres = [];
    protected $joins = [];
    protected $groupBy = [];
    protected $orderBy = [];
    protected $having = [];
    protected $limit = null;
    protected $offset = null;
    protected $values = [];      // For INSERT and UPDATE
    protected $distinct = false;

    /**
     * Start a SELECT query
     */
    public function select(array $columns = ['*']): self
    {
        $this->type = 'SELECT';
        $this->columns = $columns;
        return $this;
    }

    /**
     * Make the SELECT DISTINCT
     */
    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Start an INSERT query
     */
    public function insert(string $table, array $values = []): self
    {
        $this->type = 'INSERT';
        $this->tables = [$table];
        $this->values = $values;
        return $this;
    }

    /**
     * Start an UPDATE query
     */
    public function update(string $table, array $values = []): self
    {
        $this->type = 'UPDATE';
        $this->tables = [$table];
        $this->values = $values;
        return $this;
    }

    /**
     * Start a DELETE query
     */
    public function delete(string $table): self
    {
        $this->type = 'DELETE';
        $this->tables = [$table];
        return $this;
    }

    /**
     * Specify the table for SELECT or DELETE
     */
    public function from(string $table, ?string $alias = null): self
    {
        $tableSpec = $alias ? "$table AS $alias" : $table;
        $this->tables[] = $tableSpec;
        return $this;
    }

    /**
     * Add a WHERE condition
     */
    public function where(string $column, string $operator, $value): self
    {
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        return $this;
    }

    /**
     * Add an OR WHERE condition
     */
    public function orWhere(string $column, string $operator, $value): self
    {
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'OR'
        ];
        return $this;
    }

    /**
     * Add a raw where clause
     */
    public function whereRaw(string $rawSql, array $bindings = [], string $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $rawSql,
            'bindings' => $bindings,
            'boolean' => $boolean
        ];
        return $this;
    }

    /**
     * Add a JOIN clause
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => $type
        ];
        return $this;
    }

    /**
     * Add a LEFT JOIN clause
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add a RIGHT JOIN clause
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Add GROUP BY clause
     */
    public function groupBy(...$columns): self
    {
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }

    /**
     * Add ORDER BY clause
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => $direction
        ];
        return $this;
    }

    /**
     * Add HAVING clause
     */
    public function having(string $column, string $operator, $value): self
    {
        $this->having[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        return $this;
    }

    /**
     * Set LIMIT clause
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set OFFSET clause
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Get all properties for the compiler
     */
    public function getProperties(): array
    {
        return [
            'type' => $this->type,
            'tables' => $this->tables,
            'columns' => $this->columns,
            'wheres' => $this->wheres,
            'joins' => $this->joins,
            'groupBy' => $this->groupBy,
            'orderBy' => $this->orderBy,
            'having' => $this->having,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'values' => $this->values,
            'distinct' => $this->distinct
        ];
    }
}
