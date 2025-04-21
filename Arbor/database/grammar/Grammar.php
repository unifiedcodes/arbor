<?php

namespace Arbor\database;


use Arbor\database\query\QueryBuilder;


abstract class Grammar
{
    /**
     * Parameter binding format
     */
    protected $parameterFormat = '?';

    /**
     * Identifier quotes
     */
    protected $openQuote = '"';
    protected $closeQuote = '"';

    /**
     * Compile a query to SQL
     */
    public function compile(QueryBuilder $builder): string
    {
        $properties = $builder->getProperties();
        $type = $properties['type'];

        switch ($type) {
            case 'SELECT':
                return $this->compileSelect($properties);
            case 'INSERT':
                return $this->compileInsert($properties);
            case 'UPDATE':
                return $this->compileUpdate($properties);
            case 'DELETE':
                return $this->compileDelete($properties);
            default:
                throw new \InvalidArgumentException("Unsupported query type: $type");
        }
    }

    /**
     * Quote an identifier
     */
    public function wrapIdentifier(string $value): string
    {
        if ($value === '*') {
            return $value;
        }

        // Handle table.column format
        if (strpos($value, '.') !== false) {
            $parts = explode('.', $value);
            $wrappedParts = array_map(function ($part) {
                return $this->openQuote . $part . $this->closeQuote;
            }, $parts);
            return implode('.', $wrappedParts);
        }

        return $this->openQuote . $value . $this->closeQuote;
    }

    /**
     * Format a value for SQL
     */
    protected function parameter($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        // Return parameter placeholder (specific to dialect)
        return $this->parameterFormat;
    }

    /**
     * Compile SELECT statement
     */
    protected function compileSelect(array $properties): string
    {
        $sql = 'SELECT ';

        if ($properties['distinct']) {
            $sql .= 'DISTINCT ';
        }

        // Columns
        $columns = array_map(function ($column) {
            return $this->wrapIdentifier($column);
        }, $properties['columns']);
        $sql .= implode(', ', $columns);

        // FROM
        if (!empty($properties['tables'])) {
            $sql .= ' FROM ' . implode(', ', $properties['tables']);
        }

        // JOIN
        $sql .= $this->compileJoins($properties['joins']);

        // WHERE
        $whereClause = $this->compileWheres($properties['wheres']);
        if (!empty($whereClause)) {
            $sql .= ' WHERE ' . $whereClause;
        }

        // GROUP BY
        if (!empty($properties['groupBy'])) {
            $groupBy = array_map(function ($column) {
                return $this->wrapIdentifier($column);
            }, $properties['groupBy']);
            $sql .= ' GROUP BY ' . implode(', ', $groupBy);
        }

        // HAVING
        $havingClause = $this->compileHaving($properties['having']);
        if (!empty($havingClause)) {
            $sql .= ' HAVING ' . $havingClause;
        }

        // ORDER BY
        if (!empty($properties['orderBy'])) {
            $sql .= $this->compileOrderBy($properties['orderBy']);
        }

        // LIMIT and OFFSET
        $sql .= $this->compileLimitOffset($properties['limit'], $properties['offset']);

        return $sql;
    }

    /**
     * Compile INSERT statement
     */
    protected function compileInsert(array $properties): string
    {
        $table = reset($properties['tables']);
        $values = $properties['values'];

        if (empty($values)) {
            return "INSERT INTO $table DEFAULT VALUES";
        }

        $sql = "INSERT INTO $table";

        // Handle column names and values
        $columns = array_keys(reset($values));
        $columnList = array_map(function ($column) {
            return $this->wrapIdentifier($column);
        }, $columns);

        $sql .= ' (' . implode(', ', $columnList) . ')';
        $sql .= ' VALUES ';

        $rows = [];
        foreach ($values as $row) {
            $valueList = array_map(function ($value) {
                return $this->parameter($value);
            }, $row);
            $rows[] = '(' . implode(', ', $valueList) . ')';
        }

        $sql .= implode(', ', $rows);

        return $sql;
    }

    /**
     * Compile UPDATE statement
     */
    protected function compileUpdate(array $properties): string
    {
        $table = reset($properties['tables']);
        $values = $properties['values'];

        if (empty($values)) {
            throw new \InvalidArgumentException("UPDATE query requires values");
        }

        $sql = "UPDATE $table SET ";

        $sets = [];
        foreach ($values as $column => $value) {
            $sets[] = $this->wrapIdentifier($column) . ' = ' . $this->parameter($value);
        }
        $sql .= implode(', ', $sets);

        // WHERE
        $whereClause = $this->compileWheres($properties['wheres']);
        if (!empty($whereClause)) {
            $sql .= ' WHERE ' . $whereClause;
        }

        return $sql;
    }

    /**
     * Compile DELETE statement
     */
    protected function compileDelete(array $properties): string
    {
        $table = reset($properties['tables']);

        $sql = "DELETE FROM $table";

        // WHERE
        $whereClause = $this->compileWheres($properties['wheres']);
        if (!empty($whereClause)) {
            $sql .= ' WHERE ' . $whereClause;
        }

        // LIMIT and OFFSET (some dialects might not support)
        $sql .= $this->compileLimitOffset($properties['limit'], $properties['offset']);

        return $sql;
    }

    /**
     * Compile WHERE conditions
     */
    protected function compileWheres(array $wheres): string
    {
        if (empty($wheres)) {
            return '';
        }

        $conditions = [];

        foreach ($wheres as $index => $where) {
            $boolean = ($index === 0) ? '' : $where['boolean'] . ' ';

            if ($where['type'] === 'basic') {
                $conditions[] = $boolean . $this->wrapIdentifier($where['column']) .
                    ' ' . $where['operator'] . ' ' .
                    $this->parameter($where['value']);
            } elseif ($where['type'] === 'raw') {
                $conditions[] = $boolean . $where['sql'];
            }
        }

        return implode(' ', $conditions);
    }

    /**
     * Compile JOIN clauses
     */
    protected function compileJoins(array $joins): string
    {
        if (empty($joins)) {
            return '';
        }

        $sql = '';

        foreach ($joins as $join) {
            $condition = $this->wrapIdentifier($join['first']) .
                ' ' . $join['operator'] . ' ' .
                $this->wrapIdentifier($join['second']);

            $sql .= ' ' . $join['type'] . ' JOIN ' . $join['table'] . ' ON ' . $condition;
        }

        return $sql;
    }

    /**
     * Compile HAVING conditions
     */
    protected function compileHaving(array $having): string
    {
        // Similar to WHERE compilation
        if (empty($having)) {
            return '';
        }

        $conditions = [];

        foreach ($having as $index => $condition) {
            $boolean = ($index === 0) ? '' : $condition['boolean'] . ' ';

            if ($condition['type'] === 'basic') {
                $conditions[] = $boolean . $this->wrapIdentifier($condition['column']) .
                    ' ' . $condition['operator'] . ' ' .
                    $this->parameter($condition['value']);
            }
        }

        return implode(' ', $conditions);
    }

    /**
     * Compile ORDER BY clause
     */
    protected function compileOrderBy(array $orders): string
    {
        if (empty($orders)) {
            return '';
        }

        $orderClauses = [];
        foreach ($orders as $order) {
            $orderClauses[] = $this->wrapIdentifier($order['column']) . ' ' . $order['direction'];
        }

        return ' ORDER BY ' . implode(', ', $orderClauses);
    }

    /**
     * Compile LIMIT and OFFSET clauses
     * (This method might be overridden by specific dialect grammars)
     */
    protected function compileLimitOffset(?int $limit, ?int $offset): string
    {
        $sql = '';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . $limit;
        }

        if ($offset !== null) {
            $sql .= ' OFFSET ' . $offset;
        }

        return $sql;
    }
}
