<?php

namespace Arbor\database\query;

use Closure;
use InvalidArgumentException;
use Arbor\database\query\grammar\Grammar;
use Arbor\database\query\Placeholder;
use Arbor\database\query\Expression;

// helpers
use Arbor\database\query\helpers\WhereTrait;
use Arbor\database\query\helpers\JoinTrait;
use Arbor\database\query\helpers\HelpersTrait;

/**
 * Query Builder
 * 
 * A fluent SQL query builder that provides methods to construct database queries
 * with proper parameter binding and query composition.
 * 
 * @package Arbor\database\query
 * 
 */
class Builder
{
    use WhereTrait, JoinTrait, HelpersTrait;


    /**
     * The SQL grammar instance responsible for compiling queries
     */
    protected Grammar $grammar;

    /**
     * The compiled SQL query string
     */
    protected string $compiledSql = '';

    /**
     * The compiled parameter bindings in execution order
     */
    protected array $compiledBindings = [];

    /**
     * Whether the SQL has been compiled
     */
    protected bool $sqlCompiled = false;

    /**
     * The type of query (select, insert, update, delete)
     */
    protected string $type = '';

    /**
     * The main table to query with optional alias
     * @var array{table: string|Builder|Expression, alias: string|null}
     */
    protected array $table = ['table' => '', 'alias' => null];

    /**
     * Whether to use DISTINCT in SELECT queries
     */
    protected bool $distinct = false;

    /**
     * The columns to select
     * @var array<int, array{column: string|Builder|Expression, alias: string|null}>
     */
    protected array $select = [];

    /**
     * Data for INSERT queries
     * @var array{columns: array<int, string>, values: array<int, array<int, mixed>>|null}
     */
    protected array $insert = [
        'columns' => [],
        'values' => null,
    ];

    /**
     * The table/alias to delete from
     */
    protected ?string $delete = null;

    /**
     * Data for UPDATE queries
     * @var array<string, mixed>
     */
    protected array $update = [];

    /**
     * Alias for this builder when used as a subquery
     */
    protected ?string $subAlias = null;

    /**
     * WHERE conditions
     * @var array<int, array<string, mixed>>
     */
    protected array $wheres = [];

    /**
     * JOIN clauses
     * @var array<int, array{type: string, table: string|Builder|Expression, alias: string|null, on: array<string, mixed>}>
     */
    protected array $joins = [];

    /**
     * HAVING conditions
     * @var array<int, array<string, mixed>>
     */
    protected array $havings = [];

    /**
     * GROUP BY expressions
     * @var array<int, string|Expression|Builder>
     */
    protected array $groups = [];

    /**
     * ORDER BY clauses
     * @var array<int, array{column: string|Expression|Builder, direction: string}>
     */
    protected array $orders = [];

    /**
     * LIMIT value
     */
    protected ?int $limit = null;

    /**
     * OFFSET value
     */
    protected ?int $offset = null;

    /**
     * Common Table Expressions (WITH clauses)
     * @var array<int, array{alias: string, query: Builder, recursive: bool}>
     */
    protected array $ctes = [];

    /**
     * UNION queries
     * @var array<int, array{type: string, query: Builder}>
     */
    protected array $unions = [];

    /**
     * Parameter bindings by section
     * @var array<string, array<int, mixed>>
     */
    public array $bindings = [
        'where' => [],
        'join' => [],
        'having' => [],
        'insert' => [],
        'update' => [],
    ];

    /**
     * Updates to apply during UPSERT operations on duplicate key
     * @var array<int, string>
     */
    protected array $upsertUpdates = [];


    /**
     * Create and return a new Builder instance with the same grammar
     * 
     * @param Grammar $grammar The SQL grammar instance to use
     */
    public function __construct(Grammar $grammar)
    {
        $this->grammar = $grammar;
    }


    /**
     * Build a condition array and bind values if needed.
     * Handles array, Closure, Builder, scalar inputs uniformly.
     * 
     * @param string $section The binding section (where, join, having)
     * @param Expression|Closure|Builder|string|array $left Left side of condition
     * @param mixed $right Right side of condition
     * @param string $operator Comparison operator
     * @param string $boolean Logical operator (AND/OR)
     * @param bool $negate Whether to negate the condition
     * @param string $type The type of condition (basic, nested, etc.)
     * @return array<string, mixed> Structured condition
     */
    protected function buildCondition(
        string $section,
        Expression|Closure|Builder|string|array $left,
        mixed $right = null,
        string $operator = '=',
        string $boolean = 'AND',
        bool $negate = false,
        string $type = 'basic'
    ): array {

        // Nested groups via Closure
        if ($left instanceof Closure) {
            $type = 'nested';
            $left = $this->fragment($left);
            return compact('type', 'left', 'boolean', 'negate');
        }

        // Handle array conditions
        if (is_array($left) && $right === null) {
            return $this->arrayCondition($section, $left);
        }

        // Bind scalar right-hand values
        if (is_scalar($right)) {
            $this->bindings[$section][] = $right;
            $right = Placeholder::void;
        }

        // execute right if right is a closure.
        if ($right instanceof Closure) {
            $right = $this->fragment($right);
        }

        return [
            'type'     => $type,
            'left'     => $left,
            'operator' => $operator,
            'right'    => $right,
            'boolean'  => $boolean,
            'negate'   => $negate,
        ];
    }

    /**
     * Process array conditions into structured condition format
     * 
     * @param string $section The binding section
     * @param array<mixed> $conditions Array of conditions
     * @return array<int, array<string, mixed>> Processed conditions
     */
    protected function arrayCondition(string $section, array $conditions): array
    {
        $key = array_key_first($conditions);
        $val = $conditions[$key];

        // If nested arrays like [[left, right], [left => right]]
        if (is_array($val)) {
            $group = [];
            foreach ($conditions as $condition) {
                // alternative of array_merge for perfomance consideration.
                foreach ($this->arrayCondition($section, $condition) as $result) {
                    $group[] = $result;
                }
            }
            return $group;
        }

        $normalized = [];

        if (!array_is_list($conditions)) {
            // Case: ['col' => 'val']
            $normalized = [$key, $val, '='];
        } else {
            // Case: list array
            $count = count($conditions);

            if ($count === 2) {
                // Case: ['col', 'val']
                [$col, $val] = $conditions;
                $normalized = [$col, $val, '='];
            } elseif ($count === 3) {
                // Case: ['col', '>', 'val'] → reorder
                [$col, $op, $val] = $conditions;
                $normalized = [$col, $val, $op];
            } else {
                throw new InvalidArgumentException(
                    "Invalid condition array: " . json_encode($conditions)
                );
            }
        }

        // 3. Single return path
        return [$this->buildCondition($section, ...$normalized)];
    }

    /**
     * Normalize table definitions.
     * Supports strings, arrays, and subquery fragments.
     * 
     * @param string|Closure|Builder|array $table Table name, subquery, or array definition
     * @param string|null $alias Optional table alias
     * @return array{table: mixed, alias: ?string} Normalized table definition
     */
    protected function parseTable(Closure|string|Builder|array|Expression $table, ?string $alias): array
    {
        if (is_array($table)) {
            // ['users', 'u']
            if (array_is_list($table)) {
                $table = $table[0];
                $alias = $table[1] ?? '';
            }
            // ['users' => 'u']
            else {
                $key = array_key_first($table);
                $alias = $table[$key];
                $table = $key;
            }
        }

        if ($table instanceof Closure) {
            $table = $this->fragment($table);
        }

        if (is_string($table) && preg_match('/^(.+?)\s+(?:as\s+)?(\S+)$/i', trim($table), $m)) {
            $table = $m[1];
            $alias = $m[2];
        }

        return ['table' => $table, 'alias' => $alias];
    }


    /**
     * Filter and normalize values for database operations
     * 
     * @param mixed $value The value to normalize
     * @param string $context The binding context (update, insert)
     * @return mixed The normalized value
     * @throws InvalidArgumentException If value type is invalid
     */
    protected function filterValue(mixed $value, string $context): mixed
    {
        $isValidValue = is_scalar($value)
            || $value === null
            || $value instanceof Expression;

        if (!$isValidValue) {
            $valueType = gettype($value);
            throw new InvalidArgumentException("Update values must be scalar, null, or Expression, '{$valueType}' provided");
        }

        if (is_scalar($value)) {
            $this->bindings[$context][] = $value;
            $value = Placeholder::void;
        }

        return $value;
    }

    /**
     * Filter and normalize column names
     * 
     * @param mixed $column The column name to validate
     * @return string The validated column name
     * @throws InvalidArgumentException If column is not a string or is empty
     */
    protected function filterColumn(mixed $column, string $context): mixed
    {
        if (in_array($context, ['insert', 'update'])) {
            if (!is_string($column)) {
                throw new InvalidArgumentException("Insert/Update columns must be plain strings.");
            }
            return $column;
        }

        if ($context === 'select') {
            if ($column instanceof Expression || $column instanceof Builder || is_string($column)) {
                return $column;
            }
            throw new InvalidArgumentException("Select columns must be string, Expression or Builder.");
        }

        if (in_array($context, ['condition', 'orderby'])) {
            if ($column instanceof Expression || is_string($column)) {
                return $column;
            }
            throw new InvalidArgumentException("Condition/OrderBy columns must be string or Expression.");
        }

        return $column;
    }

    /**
     * Create a subquery fragment builder
     * 
     * @param Closure $callback Function that configures the subquery
     * @return Builder The configured subquery builder
     */
    protected function fragment(Closure $callback): Builder
    {
        $qb = new static($this->grammar);

        $callback($qb);

        return $qb;
    }

    /*------------- Public methods -------------*/

    /**
     * Set the main table (or subquery) and optional alias
     * 
     * @param string|Closure|Builder|array $table Table name, subquery or array definition
     * @param string|null $alias Optional table alias
     * @return $this For method chaining
     */
    public function table(string|Closure|Builder|array $table, ?string $alias = null): static
    {
        $this->table = $this->parseTable($table, $alias);
        return $this;
    }

    /**
     * Alias this builder when used as a subquery
     * 
     * @param string $alias The alias name
     * @return $this For method chaining
     */
    public function subAlias(string $alias): static
    {
        $this->subAlias = $alias;
        return $this;
    }

    /**
     * Choose columns to select. Defaults to ['*']
     * 
     * @param array<int, string|Closure|Builder>|null $columns Columns to select
     * @return $this For method chaining
     * @throws InvalidArgumentException If column type is invalid
     */
    public function select(array|string|Expression|null $columns = null): static
    {
        $this->type = 'select';
        $columns = $columns ?: ['*'];

        // Normalize to array
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        if (is_array($columns)) {
            // supports following convention.

            // pair of name and alias ['col1'=>'c1']
            // list of names ['col1','col2']
            // list of pairs [['col1'=>'c1'], ['col2'=>'c2']]
            foreach ($columns as $key => $value) {

                [$column, $alias] = is_int($key)
                    ? [$value, null]
                    : [$key, $value];

                $this->filterColumn($column, 'select');

                $this->select[] = ['column' => $column, 'alias' => $alias];
            }
        }

        return $this;
    }

    /**
     * Add a raw SQL expression to the SELECT clause without parameter binding
     * 
     * @param string $expression Raw SQL expression to add to SELECT
     * @return $this For method chaining
     */
    public function selectRaw(string $expression): static
    {
        $this->select[] = [
            'column' => new Expression($expression),
        ];
        return $this;
    }


    /**
     * Add a WHERE condition or nested conditions
     * 
     * @param Expression|Closure|Builder|string|array $left Left side of condition or array of conditions
     * @param mixed $right Right side of condition
     * @param string $operator Comparison operator
     * @param string $boolean Logical operator (AND/OR)
     * @param bool $negate Whether to negate the condition
     * @param string $type The type of condition
     * @return $this For method chaining
     */
    public function addWhere(
        Expression|Closure|Builder|string|array $left,
        mixed $right = null,
        string $operator = '=',
        string $boolean = 'AND',
        bool $negate = false,
        string $type = 'basic'
    ): static {

        // Delegate to generic condition builder
        $condition = $this->buildCondition(
            section: 'where',
            left: $left,
            right: $right,
            operator: $operator,
            boolean: $boolean,
            negate: $negate,
            type: $type
        );

        $this->wheres[] = $condition;

        return $this;
    }

    /**
     * Add a JOIN clause with ON conditions
     * 
     * @param string|Closure|Builder|array $table Table to join
     * @param Expression|Closure|Builder|string|array $left Left side of join condition
     * @param mixed $right Right side of join condition
     * @param string $operator Comparison operator
     * @param string $type Join type (inner, left, right)
     * @param string|null $alias Optional table alias
     * @return $this For method chaining
     */
    public function addJoin(
        string|Closure|Builder|array $table,
        Expression|Closure|Builder|string|array $left,
        mixed $right = null,
        string $operator = '=',
        string $type = 'inner',
        ?string $alias = null
    ): static {

        ['table' => $table, 'alias' => $alias] = $this->parseTable($table, $alias);

        $condition = $this->buildCondition(
            section: 'join',
            left: $left,
            right: $right,
            operator: $operator,
            type: 'basic'
        );

        $this->joins[] = [
            'type'  => $type,
            'table' => $table,
            'alias' => $alias,
            'on'    => array_is_list($condition) ? $condition : [$condition],
        ];

        return $this;
    }

    /**
     * Add a HAVING condition
     * 
     * @param Expression|Closure|Builder|string|array $left Left side of condition
     * @param mixed $right Right side of condition
     * @param string $operator Comparison operator
     * @param string $boolean Logical operator (AND/OR)
     * @param bool $negate Whether to negate the condition
     * @return $this For method chaining
     */
    public function having(
        Expression|Closure|Builder|string|array $left,
        mixed $right = null,
        string $operator = '=',
        string $boolean = 'AND',
        bool $negate = false
    ): static {
        $condition = $this->buildCondition(
            section: 'having',
            left: $left,
            right: $right,
            operator: $operator,
            boolean: $boolean,
            negate: $negate,
            type: 'basic'
        );

        $this->havings = array_merge($this->havings, $condition);
        return $this;
    }

    /**
     * Add GROUP BY clause(s)
     * 
     * @param string|Expression|Builder|array<int, string|Expression|Builder> $columns Column(s) to group by
     * @return $this For method chaining
     */
    public function groupBy(string|Expression|Builder|array $columns): static
    {
        // Handle multiple columns in array
        if (is_array($columns)) {
            foreach ($columns as $column) {
                $this->groupBy($column); // recursive
            }
            return $this;
        }

        // Handle single group by expression
        $this->groups[] = $columns;

        return $this;
    }

    /**
     * Add a DISTINCT modifier to the query
     * 
     * @param bool $distinct Whether to make the query distinct
     * @return $this For method chaining
     */
    public function distinct(bool $distinct = true): static
    {
        $this->distinct = $distinct;

        return $this;
    }

    /**
     * Add ORDER BY clause
     * 
     * @param string|Expression|Builder|array<string|int, string|Expression|Builder|string> $column Column(s) to order by
     * @param string $direction Sort direction (asc/desc)
     * @return $this For method chaining
     */
    public function orderBy(
        string|Expression|Builder|array $column,
        string $direction = 'asc'
    ): static {
        if (is_array($column)) {
            foreach ($column as $columnName => $direction) {
                // If it's a list-style array (no keys), $col will be int
                if (is_int($columnName)) {
                    // example ['name', 'created_at']
                    $this->orderBy($direction); // $dir is column
                } else {
                    // example ['name' => 'asc', 'age' => 'desc']
                    $this->orderBy($columnName, $direction);
                }
            }
            return $this;
        }

        $direction = strtoupper($direction);

        if ($direction !== 'ASC' && $direction !== 'DESC') {
            throw new InvalidArgumentException("Order by can accept only 'ASC' or 'DESC' as direction '{$direction}' provided");
        }

        $this->orders[] = [
            'column'    => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * Set the LIMIT value
     * 
     * @param int $value Maximum number of rows to return
     * @return $this For method chaining
     */
    public function limit(int $value): static
    {
        $this->limit = $value;
        return $this;
    }


    /**
     * Set the OFFSET value
     * 
     * @param int $value Number of rows to skip
     * @return $this For method chaining
     */
    public function offset(int $value): static
    {
        $this->offset = $value;
        return $this;
    }

    /**
     * Add a UNION clause to the query
     * 
     * @param Closure|Builder $query The query to union with
     * @param bool $all Whether to use UNION ALL
     * @return $this For method chaining
     * @throws InvalidArgumentException If query is not a SELECT
     */
    public function union(Closure|Builder $query, bool $all = false): static
    {
        $query = $this->fragment($query);

        if (strtolower($query->getProperties('type')) !== 'select') {
            throw new InvalidArgumentException("Union accept Select only queries");
        }

        $this->unions[] = [
            'type' => $all ? 'union all' : 'union',
            'query' => $query,
        ];

        return $this;
    }

    /**
     * Add a Common Table Expression (WITH clause)
     * 
     * @param string $alias The CTE alias name
     * @param Closure|Builder $query The CTE query
     * @param bool $recursive Whether to use recursive CTE
     * @return $this For method chaining
     */
    public function with(string $alias, Closure|Builder $query, bool $recursive = false): static
    {
        $this->ctes[] = [
            'alias'     => $alias,
            'query'     => $this->fragment($query),
            'recursive' => $recursive,
        ];

        return $this;
    }

    /**
     * Add a single row to an INSERT query
     * 
     * @param array<string, mixed> $values Column-value pairs to insert
     * @param Builder|Expression|null $subQuery Optional subquery to use as values
     * @return $this For method chaining
     * @throws InvalidArgumentException If values array is empty
     */
    public function addInsert(
        array $values,
        Builder|Expression|null $subQuery = null,
    ): static {

        if (empty($values)) {
            throw new InvalidArgumentException("Insert only accepts non-empty associative arrays");
        }

        if (array_is_list($values)) {
            throw new InvalidArgumentException("Insert can only accept associative array of column=>value pair");
        }

        $this->type = 'insert';

        $finalValues = [];
        $finalColumns = [];

        foreach ($values as $column => $value) {
            // collect columns
            $finalColumns[] = $this->filterColumn($column, 'insert');
            // collect values
            $finalValues[] = $this->filterValue($value, 'insert');
        }

        // overwrite if column signature change.
        // breaks the previous added rows but it's developer's responsibility.
        if ($finalColumns !== ($this->insert['columns'] ?? [])) {
            $this->insert['columns'] = $finalColumns;
        }

        $this->insert['values'][] = $subQuery ? $subQuery : $finalValues;

        return $this;
    }

    /**
     * Add rows to an INSERT query
     * 
     * @param array<int|string, mixed> $values Row data to insert
     * @return $this For method chaining
     */
    public function insert(array $values)
    {
        if (array_is_list($values)) {

            foreach ($values as $row) {
                $this->addInsert($row);
            }

            return $this;
        }

        return $this->addInsert($values);
    }

    /**
     * Create an UPSERT query (INSERT with ON DUPLICATE KEY UPDATE)
     * 
     * @param array<string, mixed> $values Column-value pairs to insert
     * @param array<int, string> $updateColumns Columns to update on duplicate key
     * @return $this For method chaining
     * @throws InvalidArgumentException If values array is invalid
     */
    public function upsert(array $values, array $updateColumns): static
    {
        $this->type = 'upsert';
        $this->addInsert($values);
        $this->upsertUpdates = $updateColumns; // columns to update on duplicate
        return $this;
    }

    /**
     * Create an UPDATE query
     * 
     * @param array<string, mixed> $values Column-value pairs to update
     * @return $this For method chaining
     */
    public function update(array $values)
    {
        $this->type = 'update';

        if (array_is_list($values)) {
            throw new InvalidArgumentException("Update can only accept associative array of column=>value pair");
        }

        foreach ($values as $column => $value) {
            $column = $this->filterColumn($column, 'update');
            $this->update[$column] = $this->filterValue($value, 'update');
        }

        return $this;
    }

    /**
     * Create a DELETE query
     * 
     * @param string|null $alias Optional alias to delete from
     * @return $this For method chaining
     */
    public function delete(?string $alias = null)
    {
        $this->type = 'delete';

        // If alias is provided, use it;
        if ($alias) {
            $this->delete = $alias;
        }
        // Use table alias if it's set, otherwise fall back to the table name
        else {
            $this->delete = $this->table['alias'] ?? $this->table['table'];
        }

        return $this;
    }

    /**
     * Get query parameter bindings
     * 
     * @return array<string|int, mixed> Query bindings
     */
    public function getRawBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Get query builder state properties
     * 
     * @param string|null $key Optional specific property to retrieve
     * @return mixed All properties or specific property value
     */
    public function getProperties(?string $key = null): mixed
    {
        // build the snapshot map
        $props = [
            'type'     => $this->type,
            'ctes'     => $this->ctes,
            'table'    => $this->table,
            'distinct' => $this->distinct,
            'select'   => $this->select,
            'insert'   => $this->insert,
            'delete'   => $this->delete,
            'update'   => $this->update,
            'subAlias' => $this->subAlias,
            'joins'    => $this->joins,
            'wheres'   => $this->wheres,
            'havings'  => $this->havings,
            'groups'   => $this->groups,
            'orders'   => $this->orders,
            'limit'    => $this->limit,
            'offset'   => $this->offset,
            'unions'   => $this->unions,
            'upsertUpdates' => $this->upsertUpdates
        ];

        // if no key requested, return full map
        if ($key === null) {
            return $props;
        }

        // if key exists, return that value; else null
        return $props[$key] ?? null;
    }


    /**
     * Generates the SQL query string using the grammar
     * 
     * @return string The compiled SQL query string
     */
    public function toSql(): string
    {
        $this->compiledSql = $this->grammar->compile($this); // Call grammar/compiler here
        $this->compiledBindings = $this->grammar->getMergedBindings(); // Combine wheres, joins, etc.
        $this->sqlCompiled = true;

        return $this->compiledSql;
    }

    /**
     * Get all parameter bindings in correct execution order
     * 
     * @return array<mixed> All parameter bindings for the query
     */
    public function getBindings(): array
    {
        if (!$this->sqlCompiled) {
            $this->toSql(); // force compilation before bindings
        }

        return $this->compiledBindings;
    }
}
