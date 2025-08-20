<?php

namespace Arbor\database;


use Arbor\database\PdoDb;
use Arbor\database\Database;
use Arbor\database\query\Builder;
use Arbor\database\query\grammar\Grammar;

/**
 * Query
 * 
 * Decoration on Builder class, provides a chainable Builder and Query as proxy class.
 * This class extends the Builder functionality by adding execution capabilities and 
 * database interaction methods. It acts as a bridge between the query building process
 * and the actual database execution.
 * 
 * @package Arbor\database
 */
class Query extends Builder
{
    /**
     * The database instance used for executing queries
     * 
     * @var Database
     */
    protected Database $database;

    /**
     * The prepared statement object, cached after first execution
     * 
     * @var PdoDb|null
     */
    protected ?PdoDb $statement = null;

    /**
     * The last executed SQL query string, used for statement caching
     * 
     * @var string
     */
    protected string $lastSql = '';

    /**
     * The last bound parameter values, used for binding optimization
     * 
     * @var array
     */
    protected array $lastBindings = [];

    /**
     * Flag indicating whether the query has been executed
     * 
     * @var bool
     */
    protected bool $executed = false;


    /**
     * Constructor
     * 
     * Initializes the Query instance with a grammar for SQL generation
     * and a database connection for execution.
     * 
     * @param Grammar $grammar The SQL grammar instance for query building
     * @param Database $database The Database orchestrator instance
     */
    public function __construct(Grammar $grammar, Database $database)
    {
        parent::__construct($grammar);
        $this->database = $database;
    }

    /**
     * Check if the query has a LIMIT clause
     * 
     * Returns true if a LIMIT has been set on the query, false otherwise.
     * This is useful for determining if pagination or result limiting is applied.
     * 
     * @return bool True if query has a limit, false otherwise
     */
    public function hasLimit(): bool
    {
        return $this->limit !== null;
    }

    /**
     * Check if the query has GROUP BY clauses
     * 
     * Returns true if any GROUP BY clauses have been added to the query.
     * This is used internally to determine how aggregate functions should behave.
     * 
     * @return bool True if query has groups, false otherwise
     */
    protected function hasGroups(): bool
    {
        return !empty($this->groups);
    }

    /**
     * Clear all ORDER BY clauses from the query
     * 
     * Removes all ordering constraints from the query builder.
     * This is useful when creating aggregate queries where ordering is not needed.
     * 
     * @return static Returns the current instance for method chaining
     */
    public function clearOrders(): static
    {
        $this->orders = [];
        return $this;
    }

    /**
     * Clear all GROUP BY clauses from the query
     * 
     * Removes all grouping constraints from the query builder.
     * This is useful when modifying queries for different purposes.
     * 
     * @return static Returns the current instance for method chaining
     */
    public function clearGroups(): static
    {
        $this->groups = [];
        return $this;
    }

    /**
     * Clear all HAVING clauses from the query
     * 
     * Removes all HAVING constraints from the query builder.
     * This is useful when creating simplified versions of aggregate queries.
     * 
     * @return static Returns the current instance for method chaining
     */
    public function clearHavings(): static
    {
        $this->havings = [];
        return $this;
    }


    /**
     * Get or create the prepared statement
     * 
     * Returns a PdoDb instance with the prepared statement and bound values.
     * The statement is cached after first creation to improve performance.
     * If the statement doesn't exist, it creates one by converting the query
     * to SQL, preparing it, and binding the parameter values.
     * 
     * @return PdoDb The prepared statement with bound values
     */
    protected function statement(): PdoDb
    {
        $sql = $this->toSql();

        if (!$this->statement || $this->lastSql !== $sql) {
            $this->statement = $this->database
                ->getPdoDb()
                ->sql($sql)
                ->prepareStatement();
            $this->lastSql = $sql;
            $this->executed = false;
        }

        return $this->statement;
    }

    /**
     * Bind parameter values to the prepared statement
     * 
     * Binds the current query bindings to the prepared statement if they have changed.
     * This optimization prevents unnecessary re-binding of the same values and
     * resets the execution flag when new values are bound.
     * 
     * @return void
     */
    protected function bindValues(): void
    {
        $bindings = $this->getBindings();

        if ($bindings !== $this->lastBindings) {
            $this->statement()->bindValues($bindings);
            $this->lastBindings = $bindings;
            $this->executed = false;
        }
    }


    /**
     * Execute the query
     * 
     * Executes the built SQL query with the provided parameter values.
     * The statement is cached after first execution to avoid re-execution.
     * Uses the execution flag to prevent multiple executions of the same query.
     * 
     * @return PdoDb The executed statement object
     */
    public function execute(): PdoDb
    {
        if (!$this->executed) {
            $this->bindValues();
            $this->statement()->execute();
            $this->executed = true;
        }

        return $this->statement;
    }


    /**
     * Fetch all rows from the result set
     * 
     * Executes the query (if not already executed) and returns all rows
     * from the result set as an array. This method is typically used for
     * SELECT queries that are expected to return multiple rows.
     * 
     * @return mixed All rows from the result set as an array
     */
    public function fetchAll()
    {
        return $this->execute()->fetchAll();
    }


    /**
     * Fetch a single row from the result set
     * 
     * Executes the query (if not already executed) and returns the next row
     * from the result set, or false if no more rows are available.
     * This method is useful for retrieving one row at a time or when expecting
     * a single result.
     * 
     * @return mixed A single row from the result set or false if no rows available
     */
    public function fetch()
    {
        return $this->execute()->fetch();
    }


    /**
     * Fetch a single column from the next row
     * 
     * Executes the query (if not already executed) and returns a single column
     * from the next row in the result set. This is useful for queries that
     * return a single value, such as COUNT() queries or when you only need
     * one specific column value.
     * 
     * @param int $colIndex The 0-indexed column number to retrieve (default: 0)
     * @return mixed The value of the specified column or false if no data available
     */
    public function fetchColumn(int $colIndex = 0)
    {
        return $this->execute()->fetchColumn($colIndex);
    }


    /**
     * Get the number of affected rows
     * 
     * Executes the query (if not already executed) and returns the number
     * of rows affected by the last DELETE, INSERT, or UPDATE statement.
     * For SELECT statements, this method may not return the expected count
     * and fetchAll() or similar methods should be used instead.
     * 
     * @return int The number of affected rows
     */
    public function rowCount(): int
    {
        return $this->execute()->rowCount();
    }

    /**
     * Insert data into the database
     * 
     * Inserts one or multiple rows of data into the database table.
     * For single row inserts, executes immediately and returns the insert ID.
     * For multiple row inserts, returns the Query instance for method chaining.
     * 
     * @param array $values Associative array for single row or array of arrays for multiple rows
     * @return mixed Insert ID for single row, or Query instance for multiple rows
     */
    public function insert(array $values)
    {
        parent::insert($values); // Delegate to Builder

        // If it was a single row, execute immediately and return insert ID
        if (!array_is_list($values)) {
            return $this->execute()->getInsertId();
        }

        // If it was multiple rows, return $this for chaining
        return $this;
    }

    /**
     * Update existing records in the database
     * 
     * Updates records matching the WHERE conditions with the provided values.
     * Throws an exception if no WHERE conditions are specified for safety.
     * Returns the number of affected rows after execution.
     * 
     * @param array $values Associative array of column => value pairs to update
     * @return int Number of rows affected by the update
     * @throws \Exception When no WHERE conditions are specified
     */
    public function update(array $values): int
    {
        if (empty($this->wheres)) {
            throw new \Exception("Update without WHERE is forbidden for safety.");
        }

        parent::update($values);
        return $this->execute()->rowCount();
    }

    /**
     * Delete records from the database with WHERE validation
     * 
     * Deletes records matching the WHERE conditions from the specified table.
     * Throws an exception if no WHERE conditions are specified for safety.
     * Returns the number of deleted rows after execution.
     * 
     * @param string|null $alias Optional table alias for the DELETE statement
     * @return int Number of rows deleted
     * @throws \Exception When no WHERE conditions are specified
     */
    public function delete(?string $alias = null): int
    {
        if (empty($this->wheres)) {
            throw new \Exception("Delete without WHERE is forbidden for safety.");
        }

        parent::delete($alias);
        return $this->execute()->rowCount();
    }

    /**
     * Force delete records without WHERE validation
     * 
     * Deletes records from the database without requiring WHERE conditions.
     * This bypasses the safety check and should be used with extreme caution
     * as it can delete all records in a table if no conditions are specified.
     * 
     * @param string|null $alias Optional table alias for the DELETE statement
     * @return int Number of rows deleted
     */
    public function forceDelete(?string $alias = null): int
    {
        parent::delete($alias);
        return $this->execute()->rowCount();
    }

    /**
     * Execute a SELECT query and return all matching records
     * 
     * Builds and executes a SELECT statement with the specified columns
     * (or all columns if none specified) and returns all matching rows.
     * This is equivalent to calling select() followed by fetchAll().
     * 
     * @param array|null $columns Array of column names to select, or null for all columns
     * @return mixed Array of all matching records
     */
    public function get(?array $columns = null)
    {
        parent::select($columns);
        return $this->execute()->fetchAll();
    }

    /**
     * Execute a SELECT query and return the first matching record
     * 
     * Builds and executes a SELECT statement with LIMIT 1 (if no limit exists)
     * and returns the first matching row or null if no records are found.
     * This is useful when you expect only one result or want the first match.
     * 
     * @return mixed|null The first matching record or null if none found
     */
    public function first()
    {
        // If no limit already, force LIMIT 1
        if (!$this->hasLimit()) {
            $this->limit(1);
        }

        $row = $this->execute()->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Extract a specific column's values from the query results
     * 
     * Executes the query and returns an array containing only the values
     * from the specified column. Optionally uses another column as array keys.
     * This is useful for creating lists or lookup arrays from database results.
     * 
     * @param string $column The column name to extract values from
     * @param string|null $key Optional column name to use as array keys
     * @return array Array of column values, optionally keyed by another column
     */
    public function pluck(string $column, ?string $key = null): array
    {
        $results = $this->execute()->fetchAll(\PDO::FETCH_ASSOC);

        $values = [];

        foreach ($results as $row) {
            $value = $row[$column] ?? null;

            if ($key !== null) {
                $k = $row[$key] ?? null;
                if ($k !== null) {
                    $values[$k] = $value;
                }
            } else {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * Get a single column value from the first matching record
     * 
     * Executes the query, retrieves the first matching record, and returns
     * the value of the specified column. Returns null if no record is found
     * or if the column doesn't exist in the result.
     * 
     * @param string $column The column name to retrieve the value from
     * @return mixed The column value from the first record, or null if not found
     */
    public function value(string $column)
    {
        $row = $this->first();
        return $row[$column] ?? null;
    }

    /**
     * Check if any records match the query conditions
     * 
     * Creates a simplified version of the query that selects "1" with LIMIT 1
     * to efficiently check for record existence without retrieving full data.
     * Clears unnecessary ORDER BY, GROUP BY, and HAVING clauses for performance.
     * 
     * @return bool True if at least one matching record exists, false otherwise
     */
    public function exists(): bool
    {
        // Clone the builder so we don't mutate the original
        $clone = clone $this;

        // Reset unnecessary parts
        $clone->clearOrders()->clearGroups()->clearHavings();

        // Replace columns with COUNT(1) or 1 for efficiency
        $clone->selectRaw('1')->limit(1);

        $result = $clone->execute()->fetchColumn();

        return $result !== false;
    }

    /**
     * Count the number of records matching the query
     * 
     * Executes a COUNT aggregate function on the specified column (or all records).
     * When GROUP BY clauses are present, returns an array of count results.
     * Otherwise, returns a single integer count. Clears ORDER BY and HAVING
     * clauses as they don't affect count results.
     * 
     * @param string $column The column to count, defaults to '*' for all records
     * @return mixed Integer count for simple queries, array for grouped queries
     */
    public function count(string $column = '*')
    {
        $clone = clone $this;
        $clone->clearOrders()->clearHavings();
        $clone->selectRaw("COUNT($column) as aggregate");

        $stmt = $clone->execute();

        return $clone->hasGroups() ? $stmt->fetchAll() : (int) $stmt->fetchColumn();
    }

    /**
     * Calculate the sum of a numeric column
     * 
     * Executes a SUM aggregate function on the specified column.
     * When GROUP BY clauses are present, returns an array of sum results.
     * Otherwise, returns a single float value. Clears ORDER BY and HAVING
     * clauses as they don't affect sum calculations.
     * 
     * @param string $column The numeric column to sum
     * @return mixed Float sum for simple queries, array for grouped queries
     */
    public function sum(string $column)
    {
        $clone = clone $this;
        $clone->clearOrders()->clearHavings();
        $clone->selectRaw("SUM($column) as aggregate");

        $stmt = $clone->execute();

        return $clone->hasGroups() ? $stmt->fetchAll() : (float) $stmt->fetchColumn();
    }

    /**
     * Calculate the average of a numeric column
     * 
     * Executes an AVG aggregate function on the specified column.
     * When GROUP BY clauses are present, returns an array of average results.
     * Otherwise, returns a single float value. Clears ORDER BY and HAVING
     * clauses as they don't affect average calculations.
     * 
     * @param string $column The numeric column to average
     * @return mixed Float average for simple queries, array for grouped queries
     */
    public function avg(string $column)
    {
        $clone = clone $this;
        $clone->clearOrders()->clearHavings();
        $clone->selectRaw("AVG($column) as aggregate");

        $stmt = $clone->execute();

        return $clone->hasGroups() ? $stmt->fetchAll() : (float) $stmt->fetchColumn();
    }

    /**
     * Find the minimum value in a column
     * 
     * Executes a MIN aggregate function on the specified column.
     * When GROUP BY clauses are present, returns an array of minimum results.
     * Otherwise, returns the single minimum value. Clears ORDER BY and HAVING
     * clauses as they don't affect minimum value determination.
     * 
     * @param string $column The column to find the minimum value for
     * @return mixed Minimum value for simple queries, array for grouped queries
     */
    public function min(string $column)
    {
        $clone = clone $this;
        $clone->clearOrders()->clearHavings();
        $clone->selectRaw("MIN($column) as aggregate");

        $stmt = $clone->execute();

        return $clone->hasGroups() ? $stmt->fetchAll() : $stmt->fetchColumn();
    }

    /**
     * Find the maximum value in a column
     * 
     * Executes a MAX aggregate function on the specified column.
     * When GROUP BY clauses are present, returns an array of maximum results.
     * Otherwise, returns the single maximum value. Clears ORDER BY and HAVING
     * clauses as they don't affect maximum value determination.
     * 
     * @param string $column The column to find the maximum value for
     * @return mixed Maximum value for simple queries, array for grouped queries
     */
    public function max(string $column)
    {
        $clone = clone $this;
        $clone->clearOrders()->clearHavings();
        $clone->selectRaw("MAX($column) as aggregate");

        $stmt = $clone->execute();

        return $clone->hasGroups() ? $stmt->fetchAll() : $stmt->fetchColumn();
    }
}
