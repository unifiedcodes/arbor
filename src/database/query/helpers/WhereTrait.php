<?php

namespace Arbor\database\query\helpers;

use Arbor\database\query\Builder;
use Arbor\database\query\Expression;
use Closure;
use InvalidArgumentException;

/**
 * Trait providing WHERE clause helper methods for the query builder.
 * 
 * This trait contains methods for constructing SQL WHERE clauses with various
 * conditions and operators.
 * 
 * @package Arbor\database\query\helpers
 * 
 * Examples:
 * 
 * Basic where conditions:
 * $query->where('status', 'active');                  // WHERE status = 'active'
 * $query->where('age', '>', 18);                      // WHERE age > 18
 * $query->where([                                     // WHERE status = 'active' AND role = 'admin'
 *     'status' => 'active',
 *     'role' => 'admin'
 * ]);
 * 
 * Nested conditions:
 * $query->where(function($q) {                        // WHERE (email = 'test@example.com' OR username = 'test')
 *     $q->where('email', 'test@example.com')
 *       ->orWhere('username', 'test');
 * });
 * 
 * Combined conditions:
 * $query->where('status', 'active')                   // WHERE status = 'active' AND (age > 18 OR role = 'admin')
 *       ->where(function($q) {
 *           $q->where('age', '>', 18)
 *             ->orWhere('role', 'admin');
 *       });
 * 
 */
trait WhereTrait
{
    /**
     * Add a basic WHERE clause.
     * 
     * Examples:
     * $query->where('status', 'active');              // WHERE status = 'active'
     * $query->where('age', '>', 18);                  // WHERE age > 18
     * $query->where('score', '=', 100);               // WHERE score = 100
     * 
     */
    public function where(
        Expression|Closure|Builder|string|array $left,
        mixed $right = null,
        string $operator = '='
    ): static {
        return $this->addWhere($left, $right, $operator, 'AND');
    }

    /**
     * Add a WHERE NOT clause.
     * 
     * Examples:
     * $query->whereNot('status', 'inactive');         // WHERE NOT (status = 'inactive')
     * $query->whereNot('age', '<', 18);               // WHERE NOT (age < 18)
     * $query->whereNot('role', 'guest');              // WHERE NOT (role = 'guest')
     */
    public function whereNot(
        Expression|Closure|Builder|string|array $left,
        mixed $right = null,
        string $operator = '='
    ): static {
        return $this->addWhere($left, $right, $operator, 'AND', true);
    }

    /**
     * Add an OR WHERE clause.
     * 
     * Examples:
     * $query->where('status', 'active')               // WHERE status = 'active' OR role = 'admin'
     *       ->orWhere('role', 'admin');
     * 
     * $query->where('age', '>=', 18)                  // WHERE age >= 18 OR parental_consent = true
     *       ->orWhere('parental_consent', true);
     */
    public function orWhere(
        Expression|Closure|Builder|string|array $left,
        mixed $right = null,
        string $operator = '='
    ): static {
        return $this->addWhere($left, $right, $operator, 'OR');
    }

    /**
     * Add an OR WHERE NOT clause.
     * 
     * Examples:
     * $query->where('status', 'active')               // WHERE status = 'active' OR NOT (role = 'guest')
     *       ->orWhereNot('role', 'guest');
     * 
     * $query->where('age', '>=', 18)                  // WHERE age >= 18 OR NOT (blocked = true)
     *       ->orWhereNot('blocked', true);
     */
    public function orWhereNot(
        Expression|Closure|Builder|string|array $left,
        mixed $right = null,
        string $operator = '='
    ): static {
        return $this->addWhere($left, $right, $operator, 'OR', true);
    }

    /**
     * Add a WHERE IN clause.
     * 
     * Examples:
     * $query->whereIn('status', ['active', 'pending']); // WHERE status IN ('active', 'pending')
     * 
     * With subquery:
     * $query->whereIn('id', function($q) {            // WHERE id IN (SELECT user_id FROM permissions WHERE level > 5)
     *     $q->select('user_id')
     *       ->from('permissions')
     *       ->where('level', '>', 5);
     * });
     */
    public function whereIn(
        string $column,
        array|Closure|Builder $values
    ): static {
        return $this->addWhere($column, $values, 'IN', 'AND', false, 'in');
    }

    /**
     * Add a WHERE NOT IN clause.
     * 
     * Examples:
     * $query->whereNotIn('status', ['deleted', 'banned']); // WHERE status NOT IN ('deleted', 'banned')
     * 
     * With subquery:
     * $query->whereNotIn('id', function($q) {         // WHERE id NOT IN (SELECT user_id FROM blacklist)
     *     $q->select('user_id')
     *       ->from('blacklist');
     * });
     */
    public function whereNotIn(
        string $column,
        array|Closure|Builder $values
    ): static {
        return $this->addWhere($column, $values, 'IN', 'AND', true, 'in');
    }

    /**
     * Add an OR WHERE IN clause.
     * 
     * Examples:
     * $query->where('role', 'admin')                  // WHERE role = 'admin' OR department IN ('sales', 'marketing')
     *       ->orWhereIn('department', ['sales', 'marketing']);
     */
    public function orWhereIn(string $column, array|Closure|Builder $values): static
    {
        return $this->addWhere($column, $values, 'IN', 'OR', false, 'in');
    }

    /**
     * Add an OR WHERE NOT IN clause.
     * 
     * Examples:
     * $query->where('status', 'active')               // WHERE status = 'active' OR category NOT IN ('archived', 'deleted')
     *       ->orWhereNotIn('category', ['archived', 'deleted']);
     */
    public function orWhereNotIn(string $column, array|Closure|Builder $values): static
    {
        return $this->addWhere($column, $values, 'IN', 'OR', true, 'in');
    }

    /**
     * Add a WHERE NULL clause.
     * 
     * Examples:
     * $query->whereNull('deleted_at');                // WHERE deleted_at IS NULL
     */
    public function whereNull(string $column): static
    {
        return $this->addWhere($column, null, 'IS', 'AND', false, 'null');
    }

    /**
     * Add a WHERE NOT NULL clause.
     * 
     * Examples:
     * $query->whereNotNull('email');                  // WHERE email IS NOT NULL
     */
    public function whereNotNull(string $column): static
    {
        return $this->addWhere($column, null, 'IS NOT', 'AND', true, 'null');
    }

    /**
     * Add an OR WHERE NULL clause.
     * 
     * Examples:
     * $query->where('status', 'active')               // WHERE status = 'active' OR deleted_at IS NULL
     *       ->orWhereNull('deleted_at');
     */
    public function orWhereNull(string $column): static
    {
        return $this->addWhere($column, null, 'IS', 'OR', false, 'null');
    }

    /**
     * Add an OR WHERE NOT NULL clause.
     * 
     * Examples:
     * $query->whereNull('deleted_at')                 // WHERE deleted_at IS NULL OR email IS NOT NULL
     *       ->orWhereNotNull('email');
     */
    public function orWhereNotNull(string $column): static
    {
        return $this->addWhere($column, null, 'IS NOT', 'OR', true, 'null');
    }


    protected function validBetweenValues(array $values)
    {
        if (count($values) !== 2) {
            throw new InvalidArgumentException("Where Between requires array of exactly 2 values to compare from");
        }
    }
    /**
     * Add a WHERE BETWEEN clause.
     * 
     * Examples:
     * $query->whereBetween('age', [18, 65]);          // WHERE age BETWEEN 18 AND 65
     * $query->whereBetween('created_at', 
     *     ['2023-01-01', '2023-12-31']);              // WHERE created_at BETWEEN '2023-01-01' AND '2023-12-31'
     */
    public function whereBetween(string $column, mixed $values): static
    {
        $this->validBetweenValues($values);

        return $this->addWhere($column, $values, 'BETWEEN', 'AND', false, 'between');
    }

    /**
     * Add a WHERE NOT BETWEEN clause.
     * 
     * Examples:
     * $query->whereNotBetween('age', [0, 17]);        // WHERE age NOT BETWEEN 0 AND 17
     * $query->whereNotBetween('score', [0, 59]);      // WHERE score NOT BETWEEN 0 AND 59
     */
    public function whereNotBetween(string $column, array $values): static
    {
        $this->validBetweenValues($values);

        return $this->addWhere($column, $values, 'BETWEEN', 'AND', true, 'between');
    }

    /**
     * Add an OR WHERE BETWEEN clause.
     * 
     * Examples:
     * $query->where('status', 'active')               // WHERE status = 'active' OR age BETWEEN 18 AND 65
     *       ->orWhereBetween('age', [18, 65]);
     */
    public function orWhereBetween(string $column, array $values): static
    {
        $this->validBetweenValues($values);

        return $this->addWhere($column, $values, 'BETWEEN', 'OR', false, 'between');
    }

    /**
     * Add an OR WHERE NOT BETWEEN clause.
     * 
     * Examples:
     * $query->where('department', 'sales')            // WHERE department = 'sales' OR score NOT BETWEEN 0 AND 59
     *       ->orWhereNotBetween('score', [0, 59]);
     */
    public function orWhereNotBetween(string $column, array $values): static
    {
        $this->validBetweenValues($values);

        return $this->addWhere($column, $values, 'BETWEEN', 'OR', true, 'between');
    }

    /**
     * Add a WHERE LIKE clause.
     * 
     * Examples:
     * $query->whereLike('name', 'John%');             // WHERE name LIKE 'John%'
     * $query->whereLike('email', '%@example.com');    // WHERE email LIKE '%@example.com'
     */
    public function whereLike(string $column, string $value): static
    {
        return $this->addWhere($column, $value, 'LIKE', 'AND');
    }

    /**
     * Add a WHERE NOT LIKE clause.
     * 
     * Examples:
     * $query->whereNotLike('name', 'admin%');         // WHERE name NOT LIKE 'admin%'
     * $query->whereNotLike('email', '%@spam.com');    // WHERE email NOT LIKE '%@spam.com'
     */
    public function whereNotLike(string $column, string $value): static
    {
        return $this->addWhere($column, $value, 'LIKE', 'AND', true);
    }

    /**
     * Add an OR WHERE LIKE clause.
     * 
     * Examples:
     * $query->where('status', 'active')               // WHERE status = 'active' OR name LIKE 'John%'
     *       ->orWhereLike('name', 'John%');
     */
    public function orWhereLike(string $column, string $value): static
    {
        return $this->addWhere($column, $value, 'LIKE', 'OR');
    }

    /**
     * Add an OR WHERE NOT LIKE clause.
     * 
     * Examples:
     * $query->where('role', 'admin')                  // WHERE role = 'admin' OR email NOT LIKE '%@spam.com'
     *       ->orWhereNotLike('email', '%@spam.com');
     */
    public function orWhereNotLike(string $column, string $value): static
    {
        return $this->addWhere($column, $value, 'LIKE', 'OR', true);
    }

    /**
     * Add a WHERE column to column comparison.
     * 
     * Examples:
     * $query->whereColumn('created_at', '=', 'updated_at'); // WHERE created_at = updated_at
     * $query->whereColumn('expires_at', '>', 'current_timestamp'); // WHERE expires_at > current_timestamp
     */
    public function whereColumn(string $first, string $operator, string $second): static
    {
        return $this->addWhere(
            $first,
            new Expression($second),
            $operator,
            'AND',
            false,
            'column'
        );
    }

    /**
     * Add an OR WHERE column to column comparison.
     * 
     * Examples:
     * $query->where('status', 'active')               // WHERE status = 'active' OR created_at < updated_at
     *       ->orWhereColumn('created_at', '<', 'updated_at');
     */
    public function orWhereColumn(string $first, string $operator, string $second): static
    {
        return $this->addWhere(
            $first,
            new Expression($second),
            $operator,
            'OR',
            false,
            'column'
        );
    }

    /**
     * Add a raw WHERE clause.
     * 
     * Examples:
     * $query->whereRaw('YEAR(created_at) = ?', [2023]);     // WHERE YEAR(created_at) = 2023
     * $query->whereRaw('price * ? > ?', [1.1, 100]);        // WHERE price * 1.1 > 100
     */
    public function whereRaw(string $sql, array $bindings = []): static
    {
        // Add bindings to where section
        foreach ($bindings as $binding) {
            $this->bindings['where'][] = $binding;
        }

        return $this->addWhere(
            new Expression($sql),
            null,
            null,
            'AND',
            false,
            'raw'
        );
    }

    /**
     * Add a raw OR WHERE clause.
     * 
     * Examples:
     * $query->where('status', 'active')               // WHERE status = 'active' OR LOWER(email) = 'admin@example.com'
     *       ->orWhereRaw('LOWER(email) = ?', ['admin@example.com']);
     */
    public function orWhereRaw(string $sql, array $bindings = []): static
    {
        // Add bindings to where section
        foreach ($bindings as $binding) {
            $this->bindings['where'][] = $binding;
        }

        return $this->addWhere(
            new Expression($sql),
            null,
            null,
            'OR',
            false,
            'raw'
        );
    }

    /**
     * Add a WHERE EXISTS clause.
     * 
     * Examples:
     * $query->whereExists(function($q) {              // WHERE EXISTS (SELECT 1 FROM orders WHERE orders.user_id = users.id)
     *     $q->select(1)
     *       ->from('orders')
     *       ->whereColumn('orders.user_id', '=', 'users.id');
     * });
     */
    public function whereExists(Closure|Builder $callback): static
    {
        return $this->addWhere(
            $callback,
            null,
            'EXISTS',
            'AND',
            false,
            'exists'
        );
    }

    /**
     * Add a WHERE NOT EXISTS clause.
     * 
     * Examples:
     * $query->whereNotExists(function($q) {           // WHERE NOT EXISTS (SELECT 1 FROM bans WHERE bans.user_id = users.id)
     *     $q->select(1)
     *       ->from('bans')
     *       ->whereColumn('bans.user_id', '=', 'users.id');
     * });
     */
    public function whereNotExists(Closure|Builder $callback): static
    {
        return $this->addWhere(
            $callback,
            null,
            'EXISTS',
            'AND',
            true,
            'exists'
        );
    }

    /**
     * Add an OR WHERE EXISTS clause.
     * 
     * Examples:
     * $query->where('role', 'admin')                  // WHERE role = 'admin' OR EXISTS (SELECT 1 FROM orders WHERE orders.user_id = users.id)
     *       ->orWhereExists(function($q) {
     *           $q->select(1)
     *             ->from('orders')
     *             ->whereColumn('orders.user_id', '=', 'users.id');
     *       });
     */
    public function orWhereExists(Closure|Builder $callback): static
    {
        return $this->addWhere(
            $callback,
            null,
            'EXISTS',
            'OR',
            false,
            'exists'
        );
    }

    /**
     * Add an OR WHERE NOT EXISTS clause.
     * 
     * Examples:
     * $query->where('status', 'active')               // WHERE status = 'active' OR NOT EXISTS (SELECT 1 FROM bans WHERE bans.user_id = users.id)
     *       ->orWhereNotExists(function($q) {
     *           $q->select(1)
     *             ->from('bans')
     *             ->whereColumn('bans.user_id', '=', 'users.id');
     *       });
     */
    public function orWhereNotExists(Closure|Builder $callback): static
    {
        return $this->addWhere(
            $callback,
            null,
            'EXISTS',
            'OR',
            true,
            'exists'
        );
    }

    /**
     * Add a WHERE JSON contains clause.
     * 
     * Examples:
     * $query->whereJsonContains('options', '"premium"'); // WHERE JSON_CONTAINS(options, '"premium"')
     * $query->whereJsonContains('permissions', '{"read": true}'); // WHERE JSON_CONTAINS(permissions, '{"read": true}')
     */
    public function whereJsonContains(string $column, mixed $value): static
    {
        return $this->addWhere(
            new Expression("JSON_CONTAINS($column, ?)"),
            $value,
            null,
            'AND',
            false,
            'raw'
        );
    }

    /**
     * Add a WHERE JSON not contains clause.
     * 
     * Examples:
     * $query->whereJsonNotContains('tags', '"inactive"'); // WHERE NOT JSON_CONTAINS(tags, '"inactive"')
     */
    public function whereJsonNotContains(string $column, mixed $value): static
    {
        return $this->addWhere(
            new Expression("NOT JSON_CONTAINS($column, ?)"),
            $value,
            null,
            'AND',
            false,
            'raw'
        );
    }

    /**
     * Add an OR WHERE JSON contains clause.
     * 
     * Examples:
     * $query->where('status', 'active')               // WHERE status = 'active' OR JSON_CONTAINS(roles, '"admin"')
     *       ->orWhereJsonContains('roles', '"admin"');
     */
    public function orWhereJsonContains(string $column, mixed $value): static
    {
        return $this->addWhere(
            new Expression("JSON_CONTAINS($column, ?)"),
            $value,
            null,
            'OR',
            false,
            'raw'
        );
    }

    /**
     * Add an OR WHERE JSON not contains clause.
     * 
     * Examples:
     * $query->where('status', 'active')               // WHERE status = 'active' OR NOT JSON_CONTAINS(flags, '"deleted"')
     *       ->orWhereJsonNotContains('flags', '"deleted"');
     */
    public function orWhereJsonNotContains(string $column, mixed $value): static
    {
        return $this->addWhere(
            new Expression("NOT JSON_CONTAINS($column, ?)"),
            $value,
            null,
            'OR',
            false,
            'raw'
        );
    }

    /**
     * Add a WHERE JSON length clause.
     * 
     * Examples:
     * $query->whereJsonLength('tags', '>', 3);        // WHERE JSON_LENGTH(tags) > 3
     * $query->whereJsonLength('permissions', 0);      // WHERE JSON_LENGTH(permissions) = 0
     */
    public function whereJsonLength(string $column, mixed $operator, mixed $value = null, string $boolean = 'AND'): static
    {
        // If 2 arguments are provided, assume = operator
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        return $this->addWhere(
            new Expression("JSON_LENGTH($column)"),
            $value,
            $operator,
            $boolean
        );
    }

    /**
     * Add an OR WHERE JSON length clause.
     * 
     * Examples:
     * $query->where('status', 'active')               // WHERE status = 'active' OR JSON_LENGTH(roles) > 2
     *       ->orWhereJsonLength('roles', '>', 2);
     */
    public function orWhereJsonLength(string $column, mixed $operator, mixed $value = null): static
    {
        return $this->whereJsonLength($column, $operator, $value, 'OR');
    }

    /**
     * Add a WHERE with JSON path extraction.
     * 
     * Examples:
     * $query->whereJson('data', 'settings.notifications', true);  // WHERE JSON_EXTRACT(data, '$.settings.notifications') = true
     * $query->whereJson('config', '$.theme.color', '=', 'blue');  // WHERE JSON_EXTRACT(config, '$.theme.color') = 'blue'
     */
    public function whereJson(string $column, string $path, mixed $operator, mixed $value = null, $boolean = 'AND'): static
    {
        // Handle case where operator is actually the value (meaning equality)
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        // If the path doesn't start with $, add it
        if (!str_starts_with($path, '$')) {
            $path = '$.' . $path;
        }

        // We need to handle bindings manually since we have two values to bind
        $this->bindings['where'][] = $path;
        $this->bindings['where'][] = $value;

        return $this->addWhere(
            new Expression("JSON_EXTRACT($column, ?)"),
            new Expression('?'),
            $operator,
            $boolean,
            false,
            'json_extract'
        );
    }

    /**
     * Add an OR WHERE with JSON path extraction.
     * 
     * Examples:
     * $query->where('status', 'active')               // WHERE status = 'active' OR JSON_EXTRACT(preferences, '$.notifications') = true
     *       ->orWhereJson('preferences', 'notifications', true);
     */
    public function orWhereJson(string $column, string $path, mixed $operator, mixed $value = null): static
    {
        return $this->whereJson($column, $path, $operator, $value, 'OR');
    }

    /**
     * Add a WHERE MATCH AGAINST clause (fulltext search).
     * 
     * Examples:
     * $query->whereMatchAgainst('content', 'search terms');  // WHERE MATCH (content) AGAINST ('search terms')
     * 
     * With Boolean Mode:
     * $query->whereMatchAgainst('content', '+required -excluded', true);  // WHERE MATCH (content) AGAINST ('+required -excluded' IN BOOLEAN MODE)
     * 
     * With multiple columns:
     * $query->whereMatchAgainst(['title', 'content'], 'search terms');  // WHERE MATCH (title, content) AGAINST ('search terms')
     */
    public function whereMatchAgainst(
        string|array $columns,
        string $value,
        bool $inBooleanMode = false,
        bool $inNaturalLanguageMode = false,
        bool $withQueryExpansion = false,
        string $boolean = 'AND'
    ): static {
        // Prepare columns list
        if (is_array($columns)) {
            $columnsList = implode(', ', $columns);
        } else {
            $columnsList = $columns;
        }

        // Build the MATCH AGAINST expression
        $mode = '';
        if ($inBooleanMode) {
            $mode = 'IN BOOLEAN MODE';
        } elseif ($inNaturalLanguageMode) {
            $mode = 'IN NATURAL LANGUAGE MODE';
            if ($withQueryExpansion) {
                $mode .= ' WITH QUERY EXPANSION';
            }
        } elseif ($withQueryExpansion) {
            $mode = 'WITH QUERY EXPANSION';
        }

        // Add the value binding
        $this->bindings['where'][] = $value;

        return $this->addWhere(
            new Expression("MATCH ($columnsList) AGAINST (? $mode)"),
            null,
            null,
            $boolean,
            false,
            'match_against'
        );
    }

    /**
     * Add an OR WHERE MATCH AGAINST clause (fulltext search).
     * 
     * Examples:
     * $query->where('category', 'articles')           // WHERE category = 'articles' OR MATCH (content) AGAINST ('search terms')
     *       ->orWhereMatchAgainst('content', 'search terms');
     */
    public function orWhereMatchAgainst(
        string|array $columns,
        string $value,
        bool $inBooleanMode = false,
        bool $inNaturalLanguageMode = false,
        bool $withQueryExpansion = false
    ): static {
        return $this->whereMatchAgainst($columns, $value, $inBooleanMode, $inNaturalLanguageMode, $withQueryExpansion);
    }

    /**
     * Add a WHERE DATE clause to compare a column against a date.
     * 
     * Examples:
     * $query->whereDate('created_at', '2023-01-01');  // WHERE DATE(created_at) = '2023-01-01'
     * $query->whereDate('created_at', '>=', '2023-01-01');  // WHERE DATE(created_at) >= '2023-01-01'
     */
    public function whereDate(string $column, mixed $operator, mixed $value = null): static
    {
        // If 2 arguments are provided, assume = operator
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        return $this->addWhere(
            new Expression("DATE($column)"),
            $value,
            $operator,
            'AND'
        );
    }

    /**
     * Add an OR WHERE DATE clause.
     * 
     * Examples:
     * $query->where('status', 'active')               // WHERE status = 'active' OR DATE(created_at) = '2023-01-01'
     *       ->orWhereDate('created_at', '2023-01-01');
     */
    public function orWhereDate(string $column, mixed $operator, mixed $value = null): static
    {
        // If 2 arguments are provided, assume = operator
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        return $this->addWhere(
            new Expression("DATE($column)"),
            $value,
            $operator,
            'OR'
        );
    }

    /**
     * Add a WHERE YEAR clause.
     * 
     * Examples:
     * $query->whereYear('created_at', 2023);          // WHERE YEAR(created_at) = 2023
     * $query->whereYear('birth_date', '>=', 1990);    // WHERE YEAR(birth_date) >= 1990
     */
    public function whereYear(string $column, mixed $operator, mixed $value = null): static
    {
        // If 2 arguments are provided, assume = operator
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        return $this->addWhere(
            new Expression("YEAR($column)"),
            $value,
            $operator,
            'AND'
        );
    }

    /**
     * Add an OR WHERE YEAR clause.
     * 
     * Examples:
     * $query->where('active', true)                   // WHERE active = true OR YEAR(created_at) = 2023
     *       ->orWhereYear('created_at', 2023);
     * 
     * $query->where('department', 'sales')            // WHERE department = 'sales' OR YEAR(hired_date) > 2020
     *       ->orWhereYear('hired_date', '>', 2020);
     */
    public function orWhereYear(string $column, mixed $operator, mixed $value = null): static
    {
        // If 2 arguments are provided, assume = operator
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        return $this->addWhere(
            new Expression("YEAR($column)"),
            $value,
            $operator,
            'OR'
        );
    }

    /**
     * Add a WHERE MONTH clause.
     * 
     * Examples:
     * $query->whereMonth('created_at', 1);            // WHERE MONTH(created_at) = 1 (January)
     * $query->whereMonth('birth_date', '>=', 6);      // WHERE MONTH(birth_date) >= 6 (June or later)
     */
    public function whereMonth(string $column, mixed $operator, mixed $value = null): static
    {
        // If 2 arguments are provided, assume = operator
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        return $this->addWhere(
            new Expression("MONTH($column)"),
            $value,
            $operator,
            'AND'
        );
    }

    /**
     * Add an OR WHERE MONTH clause.
     * 
     * Examples:
     * $query->where('status', 'active')               // WHERE status = 'active' OR MONTH(created_at) = 12
     *       ->orWhereMonth('created_at', 12);         // (December)
     */
    public function orWhereMonth(string $column, mixed $operator, mixed $value = null): static
    {
        // If 2 arguments are provided, assume = operator
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        return $this->addWhere(
            new Expression("MONTH($column)"),
            $value,
            $operator,
            'OR'
        );
    }

    /**
     * Add a WHERE DAY clause.
     * 
     * Examples:
     * $query->whereDay('created_at', 15);             // WHERE DAY(created_at) = 15
     * $query->whereDay('due_date', '<', 10);          // WHERE DAY(due_date) < 10
     */
    public function whereDay(string $column, mixed $operator, mixed $value = null): static
    {
        // If 2 arguments are provided, assume = operator
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        return $this->addWhere(
            new Expression("DAY($column)"),
            $value,
            $operator,
            'AND'
        );
    }

    /**
     * Add an OR WHERE DAY clause.
     * 
     * Examples:
     * $query->where('is_active', true)                // WHERE is_active = true OR DAY(created_at) = 1
     *       ->orWhereDay('created_at', 1);
     */
    public function orWhereDay(string $column, mixed $operator, mixed $value = null): static
    {
        // If 2 arguments are provided, assume = operator
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        return $this->addWhere(
            new Expression("DAY($column)"),
            $value,
            $operator,
            'OR'
        );
    }

    /**
     * Add a WHERE TIME clause.
     * 
     * Examples:
     * $query->whereTime('created_at', '09:00:00');    // WHERE TIME(created_at) = '09:00:00'
     * $query->whereTime('login_at', '>', '12:00:00'); // WHERE TIME(login_at) > '12:00:00'
     */
    public function whereTime(string $column, mixed $operator, mixed $value = null): static
    {
        // If 2 arguments are provided, assume = operator
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        return $this->addWhere(
            new Expression("TIME($column)"),
            $value,
            $operator,
            'AND'
        );
    }

    /**
     * Add an OR WHERE TIME clause.
     * 
     * Examples:
     * $query->where('status', 'active')               // WHERE status = 'active' OR TIME(created_at) > '17:00:00'
     *       ->orWhereTime('created_at', '>', '17:00:00');
     */
    public function orWhereTime(string $column, mixed $operator, mixed $value = null): static
    {
        // If 2 arguments are provided, assume = operator
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        return $this->addWhere(
            new Expression("TIME($column)"),
            $value,
            $operator,
            'OR'
        );
    }
}
