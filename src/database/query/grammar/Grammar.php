<?php

namespace Arbor\database\query\grammar;


use Arbor\database\query\Builder;
use Arbor\database\query\Expression;
use Arbor\database\query\Placeholder;
use InvalidArgumentException;
use Exception;


/**
 * Abstract Grammar class for SQL query compilation
 * 
 * This class is responsible for converting query builder objects into
 * valid SQL strings according to specific database dialect syntax rules.
 */
abstract class Grammar
{
    /**
     * Query builder properties storage
     * 
     * @var array
     */
    protected array $properties;
    protected array $bindings;

    protected Builder $builder;

    protected array $mergedBindings = [];

    /**
     * Compile a query builder into an SQL string
     * 
     * @param Builder $builder The query builder instance to compile
     * @return string The compiled SQL query string
     */
    public function compile(Builder $builder): string
    {
        $this->builder = $builder;
        $this->properties = $builder->getProperties();
        $this->bindings = $builder->getRawBindings();

        $this->mergedBindings = []; // Reset merged bindings at the start of compilation

        // compile with
        // compile intent
        $sql = $this->DML();
        // compile unioun

        return $sql;
    }


    /**
     * Compile the Data Manipulation Language (DML) part of the query
     * 
     * @return string The compiled DML statement
     * @throws Exception When encountering an undefined query type
     */
    protected function DML(): string
    {
        return match (strtolower($this->properties['type'])) {

            'select' => $this->compileSelect(),
            'insert' => $this->compileInsert(),
            'update' => $this->compileUpdate(),
            'delete' => $this->compileDelete(),
            'upsert' => $this->compileUpsert(),

            default => throw new Exception("Undefined query type encountered while trying to serialize sql")
        };
    }


    /**
     * Compile a SELECT query
     * 
     * @return string The compiled SELECT statement
     */
    protected function compileSelect(): string
    {
        $sql = ['SELECT'];

        // DISTINCT modifier
        $sql[] = !empty($this->properties['distinct']) ? 'DISTINCT' : null;

        // Columns
        $sql[] = $this->columnList($this->properties['select']);

        $sql[] = 'FROM';

        $table = $this->properties['table'];

        // TABLE identifier
        $sql[] = $this->table($table['table'], $table['alias']);

        // JOIN
        $sql[] = $this->join();

        // WHERE
        $sql[] = $this->where();

        // GROUP BY
        $sql[] = $this->group();

        // HAVING
        $sql[] = $this->having();

        // ORDER BY
        $sql[] = $this->order();

        // LIMIT & OFFSET
        $sql[] = $this->limit();


        // adding into one line and returning.
        return implode(' ', array_filter($sql));
    }

    /**
     * Compile an INSERT query into SQL string
     * 
     * @return string The compiled DELETE SQL statement
     */
    protected function compileInsert(): string
    {
        $table = $this->properties['table'];
        $insert = $this->properties['insert'];

        $sql = 'INSERT INTO ' . $this->table($table['table']);

        $columns = array_map([$this, 'wrap'], $insert['columns']);
        $sql .= ' (' . implode(', ', $columns) . ')';

        $sql .= ' VALUES ';

        $valueGroups = [];

        foreach ($insert['values'] as $valueRow) {
            // If the value is a subquery or expression
            if ($this->isFragment($valueRow)) {
                $valueGroups[] = $this->fragment($valueRow);
            } else {
                // Regular value list
                $valueGroups[] = '(' . implode(', ', array_map([$this, 'value'], $valueRow)) . ')';
            }
        }

        $sql .= implode(', ', $valueGroups);

        $this->mergeBindings('insert');

        return $sql;
    }


    /**
     * Compile an UPSERT query into SQL string
     *
     * MySQL style: INSERT ... ON DUPLICATE KEY UPDATE ...
     *
     * @return string
     */
    protected function compileUpsert(): string
    {
        $insertSql = $this->compileInsert();

        $updates = $this->properties['upsertUpdates'] ?? [];

        if (!empty($updates)) {
            $updateParts = [];
            foreach ($updates as $column) {
                $updateParts[] = $this->identifier($column) . ' = VALUES(' . $this->identifier($column) . ')';
            }
            $insertSql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updateParts);
        }

        return $insertSql;
    }


    /**
     * Compile an UPDATE query into SQL string
     * 
     * @return string The compiled DELETE SQL statement
     */
    protected function compileUpdate(): string
    {
        $sql = [];

        $table = $this->properties['table'];
        $update = $this->properties['update'];

        $sql[] = 'UPDATE ' . $this->table($table['table'], $table['alias']);

        $sql[] = $this->join();

        $sql[] = 'SET';


        $sets = [];
        foreach ($update as $column => $value) {
            $sets[] = $this->identifier($column) . ' = ' . $this->value($value);
        }

        $sql[] = implode(', ', $sets);

        $this->mergeBindings('update');

        $sql[] = $this->where();

        return implode(' ', array_filter($sql));
    }


    /**
     * Compile a DELETE query into SQL string
     * 
     * @return string The compiled DELETE SQL statement
     */
    protected function compileDelete(): string
    {
        $table = $this->properties['table'];
        $delete = $this->properties['delete'];

        $sql[] = 'DELETE';

        // Handle specific table alias for deletion
        if ($delete && $delete !== $table['table']) {
            $sql[] = $this->wrap($delete);
        }

        $sql[] = 'FROM ' . $this->table($table['table'], $table['alias']);

        $sql[] = $this->join();

        $sql[] = $this->where();

        return implode(' ', $sql);
    }

    /**
     * Compile a list of columns for SELECT statement
     * 
     * @param array $columns The columns to compile
     * @return string Comma-separated list of formatted column expressions
     */
    protected function columnList(array $columns): string
    {
        $finalColumns = [];

        foreach ($columns as $column) {
            $columnName = $column['column'];
            $alias = $column['alias'];

            if (is_string($columnName)) {
                $columnName = static::identifier($columnName);
            }

            if ($this->isFragment($columnName)) {
                $columnName = $this->fragment($columnName);
            }

            if ($alias) {
                $columnName .= $this->alias($alias);
            }

            $finalColumns[] = $columnName;
        }

        return implode(', ', $finalColumns);
    }


    /**
     * Format a table reference for use in SQL
     * 
     * @param mixed $table The table name or a subquery
     * @param string|null $alias Optional alias for the table
     * @return string The formatted table reference
     */
    protected function table(mixed $table, ?string $alias = null): string
    {
        if ($this->isFragment($table)) {
            $table = $this->fragment($table);
        } else {
            $table = static::identifier($table, $alias);
        }

        if ($alias) {
            $table .= $this->alias($alias);
        }

        return $table;
    }


    /**
     * Create an alias clause
     * 
     * @param string $alias The alias name
     * @param bool $as Whether to include the AS keyword
     * @return string The formatted alias clause
     */
    protected function alias(string $alias, $as = true): string
    {
        $sql = '';

        if ($as) {
            $sql .= ' AS ';
        }

        $sql .= static::wrap($alias);

        return $sql;
    }


    /**
     * Compile the WHERE clause of the query
     * 
     * @return string The compiled WHERE clause or empty string if no conditions
     */
    protected function where(): string
    {
        if (empty($this->properties['wheres'])) {
            return '';
        }

        $wheres = $this->conditionList($this->properties['wheres']);

        $this->mergeBindings('where');

        return 'WHERE ' . $wheres;
    }


    /**
     * Compile a list of conditions for WHERE or HAVING clauses
     * 
     * @param array $conditions The conditions to compile
     * @param string $defaultBoolean The default boolean operator (AND/OR)
     * @return string The compiled conditions as a string
     */
    protected function conditionList(array $conditions): string
    {
        if (empty($conditions)) {
            return '';
        }

        // Multiple conditions
        if (isset($conditions[0]) && is_array($conditions[0])) {

            $parts = [];

            foreach ($conditions as $key => $condition) {

                if ($key > 0) {
                    $parts[] = $condition['boolean'];
                }

                // if nested conditions
                if (array_is_list($condition)) {
                    $parts[] = $this->conditionList($condition);
                    continue;
                }

                $parts[] = $this->condition($condition);
            }

            return implode(' ', $parts);
        }

        // Single condition
        return $this->condition($conditions);
    }


    /**
     * Compile a single condition for WHERE or HAVING clauses
     * 
     * @param array $condition The condition definition
     * @return string The compiled condition
     */
    protected function condition(array $condition): string
    {
        $type = $condition['type'] ?? 'basic';
        $operator = $condition['operator'] ?? '=';
        $negate = !empty($condition['negate']);

        $left = $condition['left'];
        $right = $condition['right'] ?? null;

        $sql = '';

        // if left is Builder and type = nested, extract and compile conditions and wrap in paranthesis.
        // early returning
        if ($left instanceof Builder && $type === 'nested') {

            $sql .= $negate ? "NOT " : '';
            $sql = '(' . $this->conditionList($left->getProperties('wheres')) . ')';

            return $sql;
        }

        // if left is string, consider an identifier
        elseif (is_string($left)) {
            $left = static::identifier($left);
        }

        // if left is a fragment , compile it.
        elseif ($this->isFragment($left)) {
            $left = $this->fragment($left);
        }

        // if type = in , and right is notfragment, create comma seperated values.
        if (is_array($right) && $type == 'in') {
            $right = '(' . implode(', ', array_map([$this, 'value'], $right)) . ')';
        }

        // if type = Between and right is array
        elseif (is_array($right) && $type == 'between') {
            $from = $this->value($right[0]);
            $to = $this->value($right[1]);
        }
        // compile value normally.
        else {
            $right = $this->value($right);
        }


        switch ($type) {
            case 'basic':
                $sql = "$left $operator $right";
                break;

            case 'between':

                $sql  = "$left BETWEEN $from AND $to";
                break;

            case 'exists':
                $sql = 'EXISTS ' . $left;
                break;
        }

        return $negate ? "NOT ($sql)" : $sql;
    }


    /**
     * Format a value for use in SQL
     * 
     * @param mixed $value The value to format
     * @return string The formatted value
     * @throws InvalidArgumentException When an invalid value type is provided
     */
    protected function value(mixed $value): string
    {
        if ($value === Placeholder::void) {
            return static::placeholder();
        }

        if ($value === null) {
            return 'NULL';
        }

        if ($this->isFragment($value)) {
            return $this->fragment($value);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        throw new InvalidArgumentException('Invalid Value Type is provided, only Scalar, Builder, Expression, Placeholder are supported');
    }


    /**
     * Check if a value is a query fragment (Builder or Expression)
     * 
     * @param mixed $input The value to check
     * @return bool True if the value is a fragment, false otherwise
     */
    protected function isFragment(mixed $input): bool
    {
        return $input instanceof Builder || $input instanceof Expression;
    }


    /**
     * Compile a query fragment
     * 
     * @param Builder|Expression $input The fragment to compile
     * @return string The compiled fragment
     * @throws InvalidArgumentException When an invalid value is provided
     */
    protected function fragment(Builder|Expression $input): string
    {
        if ($input instanceof Builder) {

            // new instance of Grammar,
            // calling static make sure correct child grammar instance is invoked.
            $subGrammar = new static();

            // Compile the subquery
            $sql = $subGrammar->compile($input);

            // Merge the subquery's bindings into the current mergedBindings
            $this->mergedBindings = array_merge($this->mergedBindings, $subGrammar->getMergedBindings());

            $subQuery = '(' . trim($sql) . ')';

            // Handle subqueries with alias
            $alias = $input->getProperties('subAlias');

            if ($alias) {
                $subQuery .= $this->alias($alias);
            }

            return $subQuery;
        }

        if ($input instanceof Expression) {
            return (string) $input; // Expression is assumed safe
        }

        throw new InvalidArgumentException('Invalid value provided for SQL compilation.');
    }


    /**
     * Format an identifier (table or column name) for use in SQL
     * 
     * @param string $identifier The identifier to format
     * @return string The formatted identifier
     */
    protected static function identifier(string $identifier): string
    {
        // Handle * special case
        if ($identifier === '*') {
            return '*';
        }

        // Handle qualified identifiers (schema.table or table.column)
        if (strpos($identifier, '.') !== false) {
            $parts = array_map([static::class, 'wrap'], explode('.', $identifier));
            return implode('.', $parts);
        }

        // Single identifier
        return static::wrap($identifier);
    }


    /**
     * Wrap an identifier in database-specific quotes
     * 
     * @param string $input The identifier to wrap
     * @return string The wrapped identifier
     */
    abstract protected static function wrap(string $input): string;

    /**
     * Get a database-specific placeholder for prepared statements
     * 
     * @return string The placeholder string
     */
    abstract protected static function placeholder(): string;


    /**
     * Compile the JOIN clauses of the query
     * 
     * @return string|null The compiled JOIN clauses or null if no joins
     */
    protected function join(): string
    {
        if (empty($this->properties['joins'])) {
            return '';
        }

        $joins = [];

        foreach ($this->properties['joins'] as $join) {
            $joinType = strtoupper($join['type']);
            $table = $this->table($join['table'], $join['alias']);
            $on = $this->conditionList($join['on']);
            $joins[] = "$joinType JOIN $table ON $on";
        }

        $this->mergeBindings('join');

        return implode(' ', $joins);
    }


    /**
     * Compile the ORDER BY clause of the query
     * 
     * @return string The compiled ORDER BY clause or empty string if no ordering
     */
    protected function order(): string
    {
        if (empty($this->properties['orders'])) {
            return '';
        }

        $orders = [];

        foreach ($this->properties['orders'] as $order) {
            $column = $order['column'];
            $direction = $order['direction'];

            if ($this->isFragment($column)) {
                $column = $this->fragment($column);
            } else {
                $column = $this->identifier($column);
            }

            $orders[] = $column . ' ' . $direction;
        }

        return 'ORDER BY ' . implode(', ', $orders);
    }


    /**
     * Compile the GROUP BY clause of the query
     * 
     * @return string The compiled GROUP BY clause or empty string if no grouping
     */
    protected function group(): string
    {
        if (empty($this->properties['groups'])) {
            return '';
        }

        $groups = [];

        foreach ($this->properties['groups'] as $group) {

            if ($this->isFragment($group)) {
                $groups[] = $this->fragment($group);
            } else {
                $groups[] = $this->identifier($group);
            }
        }

        return 'GROUP BY ' . implode(', ', $groups);
    }


    /**
     * Compile the HAVING clause of the query
     * 
     * @return string The compiled HAVING clause or empty string if no conditions
     */
    protected function having(): string
    {
        if (empty($this->properties['havings'])) {
            return '';
        }

        $havings = $this->conditionList($this->properties['havings']);

        $this->mergeBindings('having');

        return 'HAVING ' . $havings;
    }


    /**
     * Compile the LIMIT and OFFSET clauses of the query
     * 
     * @return string The compiled LIMIT and OFFSET clauses or empty string if neither is set
     */
    protected function limit(): string
    {
        $sql = [];

        if (isset($this->properties['limit'])) {
            $sql[] = 'LIMIT ' . (int)$this->properties['limit'];
        }

        if (isset($this->properties['offset'])) {
            $sql[] = 'OFFSET ' . (int)$this->properties['offset'];
        }

        return implode(' ', $sql);
    }

    /**
     * Merge bindings of a specific type into the mergedBindings array
     * 
     * This method collects all bindings of the specified type from the query builder's
     * raw bindings and adds them to the unified mergedBindings array. This is essential
     * for ensuring proper parameter order when executing prepared statements.
     * 
     * @param string $type The type of binding to merge (where, join, having, etc.)
     * @return void
     */
    protected function mergeBindings(string $type): void
    {
        if (isset($this->bindings[$type])) {
            foreach ($this->bindings[$type] as $binding) {
                $this->mergedBindings[] = $binding;
            }
        }
    }

    /**
     * Get all parameter bindings that have been merged during query compilation
     * 
     * @return array The array of all parameter values to bind
     */
    public function getMergedBindings(): array
    {
        return $this->mergedBindings;
    }
}
