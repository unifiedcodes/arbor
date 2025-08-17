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
     * This method supports method chaining.
     * 
     * @param array $values Associative array of parameter values
     * @return self Returns the current instance for method chaining
     */
    public function values(array $values): self
    {
        $this->values = $values;
        return $this;
    }


    /**
     * Execute the query
     * 
     * Executes the built SQL query with the provided parameter values.
     * The statement is cached after first execution to avoid re-execution.
     * 
     * @return PdoDb The executed statement object
     */
    public function execute(): PdoDb
    {
        if (!$this->statement) {
            $this->statement = $this->database->execute($this->toSql(), $this->values);
        }

        return $this->statement;
    }


    /**
     * Fetch all rows from the result set
     * 
     * Executes the query (if not already executed) and returns all rows
     * from the result set as an array.
     * 
     * @return mixed All rows from the result set
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
     * 
     * @return mixed A single row from the result set or false
     */
    public function fetch()
    {
        return $this->execute()->fetch();
    }


    /**
     * Fetch a single column from the next row
     * 
     * Executes the query (if not already executed) and returns a single column
     * from the next row in the result set.
     * 
     * @param int $colIndex The 0-indexed column number to retrieve (default: 0)
     * @return mixed The value of the specified column or false
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
     * 
     * @return int The number of affected rows
     */
    public function rowCount(): int
    {
        return $this->execute()->rowCount();
    }
}
