<?php

namespace Arbor\database\query;

use Closure;
use InvalidArgumentException;
use Arbor\database\query\Placeholder;
use Arbor\database\query\Expression;
use Arbor\database\query\grammar\Grammar;
use Arbor\database\query\grammar\MySQLGrammar;

// helpers
use Arbor\database\query\helpers\WhereTrait;
use Arbor\database\query\helpers\JoinTrait;

/**
 * Query Builder
 */
class Builder
{
    use WhereTrait, JoinTrait;

    protected Grammar $grammar;
    public string $type = '';

    // action
    public array $table = ['table' => '', 'alias' => null];
    public bool $distinct = false;
    public array $columns = [];
    public ?string $subAlias = null;

    // conditions
    public array $wheres = [];
    public array $joins = [];
    public array $havings = [];

    // order
    public array $groups = [];
    public array $orders = [];

    // pointer
    protected int|null $limit = null;
    protected int|null $offset = null;


    public array $bindings = [
        'where' => [],
        'join' => [],
        'having' => []
    ];


    public function __construct(?Grammar $grammar = null)
    {
        $this->grammar = $grammar ?: new MySQLGrammar();
    }

    /**
     * Set the main table (or subquery) and optional alias.
     */
    public function table(string|Closure|Builder|array $table, ?string $alias = null): static
    {
        $this->table = $this->parseTable($table, $alias);
        return $this;
    }

    /**
     * Alias this builder when used as a subquery.
     */
    public function subAlias(string $alias): static
    {
        $this->subAlias = $alias;
        return $this;
    }

    /**
     * Choose columns to select. Defaults to ['*'].
     * @param array<int, string|Closure|Builder> | null $columns
     */
    public function select(?array $columns = null): static
    {
        $columns = $columns ?: ['*'];

        foreach ($columns as $key => $col) {
            if ($col instanceof Closure) {
                $col = $this->fragment($col);
            }

            // checking column type.
            $validCol = is_string($col) || $col instanceof Builder || $col instanceof Expression;

            if (!$validCol) {
                throw new InvalidArgumentException("column name is invalid in");
            }

            $this->columns[] = [
                'column' => $col,
                'alias'  => is_int($key) ? null : (string)$key,
            ];
        }

        $this->type = 'select';

        return $this;
    }

    /**
     * Add a WHERE condition or nested conditions.
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

        $this->wheres = array_merge($this->wheres, $condition);

        return $this;
    }

    /**
     * Add a JOIN clause with ON conditions.
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
            'on'    => $condition,
        ];

        return $this;
    }


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

    //  Add GROUP BY clause(s).
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


    // adds a Distinct modifier 
    public function distinct(bool $distinct = true)
    {
        $this->distinct = $distinct;
    }


    // adds orderby
    public function orderBy(
        string|Expression|Builder|array $column,
        string $direction = 'asc'
    ): static {
        if (is_array($column)) {
            foreach ($column as $col => $dir) {
                // If it's a list-style array (no keys), $col will be int
                if (is_int($col)) {
                    // example ['name', 'created_at']
                    $this->orderBy($dir); // $dir is column
                } else {
                    // example ['name' => 'asc', 'age' => 'desc']
                    $this->orderBy($col, $dir);
                }
            }
            return $this;
        }

        $this->orders[] = [
            'column'    => $column,
            'direction' => strtolower($direction),
        ];

        return $this;
    }

    // helper method, later move to general helper method trait
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


    // sets limit value
    public function limit(int $value): static
    {
        $this->limit = $value;
        return $this;
    }

    // sets offset value
    public function offset(int $value): static
    {
        $this->offset = $value;
        return $this;
    }

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

    // ================ UTILITIES ================


    /**
     * Build a condition array and bind values if needed.
     * Handles array, Closure, Builder, scalar inputs uniformly.
     * @return array<string, mixed>
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

        return [
            'type'     => $type,
            'left'     => $left,
            'operator' => $operator,
            'right'    => $right,
            'boolean'  => $boolean,
            'negate'   => $negate,
        ];
    }


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

        // If list-style array: [left, right, operator?]
        if (array_is_list($conditions)) {
            return [$this->buildCondition($section, ...$conditions)];
        }

        // Else associative array: [column => value]
        return [$this->buildCondition($section, $key, $val, '=')];
    }


    /**
     * Normalize table definitions.
     * Supports strings, arrays, and subquery fragments.
     * @return array{table: mixed, alias: ?string}
     */
    protected function parseTable(string|Closure|Builder|array $table, ?string $alias): array
    {
        // [ 'u' => 'users' ] or [ 'users', 'u' ]
        if (is_array($table)) {
            $key = array_key_first($table);
            $val = $table[$key];

            if (is_int($key)) {
                [$table, $alias] = $table;
            } else {
                $table = $val;
            }
        }

        if (is_string($table) && preg_match('/^(.+?)\s+(?:as\s+)?(\S+)$/i', trim($table), $m)) {
            $table = $m[1];
            $alias = $m[2];
        }

        if ($table instanceof Closure) {
            $table = $this->fragment($table);
        }

        return ['table' => $table, 'alias' => $alias];
    }

    /**
     * Create a subquery fragment builder.
     */
    protected function fragment(Closure $callback): Builder
    {
        $qb = new static($this->grammar);
        $callback($qb);
        return $qb;
    }
}
