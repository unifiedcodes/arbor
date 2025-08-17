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
     * Flag indicating whether the query has been executed
     * 
     * @var bool
     */
    protected bool $executed = false;

    /**
     * Parameter values to be bound to the prepared statement
     * 
     * @var array
     */
    protected array $values = [];


    /**
     * Constructor
     * 
     * Initializes the Query instance with a grammar for SQL generation
     * and a database connection for execution.
     * 
     * @param Grammar $grammar The SQL grammar instance for query building
     * @param Database $database The database connection instance
     */
    public function __construct(Grammar $grammar, Database $database)
    {
        parent::__construct($grammar);
        $this->database = $database;
    }


    /**
     * Set parameter values for the query
     * 
     * Sets the values that will be bound to placeholders in the prepared statement.
     * This method supports method chaining and resets the statement cache and 
     * execution flag to ensure fresh execution.
     * 
     * @param array $values Associative array of parameter values
     * @return self Returns the current instance for method chaining
     */
    public function values(array $values): self
    {
        $this->values = $values;
        $this->statement = null;   // reset prepared statement
        $this->executed = false;   // reset execution flag
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
    public function statement(): PdoDb
    {
        if (!$this->statement) {
            $this->statement = $this->database
                ->getPdoDb()
                ->sql($this->toSql())
                ->prepareStatement()
                ->bindValues($this->values);
        }

        return $this->statement;
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
}
