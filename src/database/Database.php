<?php

namespace Arbor\database;


use Closure;
use Arbor\database\PdoDb;
use Arbor\database\query\Builder;
use Arbor\database\connection\Connection;
use Arbor\database\connection\ConnectionPool;
use Arbor\database\utility\GrammarResolver;
use Arbor\database\query\grammar\Grammar;
use Arbor\database\utility\Placeholders;

/**
 * 
 * Database Orchestrator Class
 * 
 * This class serves as the main orchestrator for database operations in the Arbor framework.
 * It coordinates connections, grammar rules, and query building to provide a unified interface
 * for database interactions. The class follows a fluent interface design pattern for method chaining.
 * 
 * @package Arbor\database
 * 
 */

class Database
{
    /**
     * Connection pool manager
     * 
     * @var ConnectionPool
     */
    protected ConnectionPool $connectionPool;

    /**
     * Current active database connection
     * 
     * @var Connection
     */
    protected Connection $connection;


    /**
     * Grammar resolver instance for loading appropriate SQL grammar classes
     * 
     * @var GrammarResolver
     */
    protected GrammarResolver $grammarResolver;

    /**
     * Current active grammar instance
     * 
     * @var Grammar
     */
    protected Grammar $grammar;


    /**
     * Constructor for Database class
     * 
     * Initializes a new Database orchestrator with an optional connection pool.
     * 
     * @param ConnectionPool|null $connectionPool Optional connection pool manager
     */
    public function __construct(ConnectionPool|null $connectionPool = null)
    {
        $this->connectionPool = $connectionPool;

        $this->grammarResolver = new GrammarResolver(
            'Arbor\\database\\query\\grammar\\'
        );
    }


    /**
     * Set the active database connection
     * 
     * Accepts either a Connection instance, a connection name string, or uses the default connection.
     * 
     * @param string|Connection|null $connection Connection instance or name
     * @return static Returns self for method chaining
     * 
     */
    public function withConnection(string|Connection|null $connection = null): static
    {
        if ($connection instanceof Connection) {
            $this->connection = $connection;
            return $this;
        }

        if (is_string($connection)) {
            $this->connection = $this->connectionPool->acquireConnection($connection);
            return $this;
        }

        // fallback to default
        $this->connection = $this->connectionPool->acquireConnection('default');

        return $this;
    }


    /**
     * Set the SQL grammar for the specific database driver
     * 
     * If no driver is specified, it will use the driver from the current connection.
     * 
     * @param string|null $driver Database driver identifier
     * @return static Returns self for method chaining
     * 
     */
    public function withGrammar($driver = null): static
    {
        if (!$driver) {
            // initialize a non initialized connection.
            $this->connection ?? $this->withConnection();

            $driver = $this->connection->getDriver();
        }

        $this->grammar = $this->grammarResolver->resolve($driver);

        return $this;
    }


    /**
     * Entry point for creating a query builder instance
     * 
     * Initializes connection and grammar if not already set, then creates
     * a new Builder instance with the specified table.
     * 
     * @param string|Closure|Builder|array $table Table name, subquery or expression
     * @param string|null $alias Optional table alias
     * @return Builder Returns the query builder instance for chaining
     */
    public function table(
        string|Closure|Builder|array $table,
        ?string $alias = null
    ) {
        $this->connection ?? $this->withConnection();
        $this->grammar ?? $this->withGrammar();

        return (new Builder($this->grammar))->table($table, $alias);
    }


    // =========== EXECUTION METHODS ============

    /**
     * Get a PDO database wrapper instance
     * 
     * Creates and returns a PdoDb instance using the current connection
     * and a new Placeholders utility for parameter handling.
     * 
     * @return PdoDb The PDO database wrapper instance
     * 
     */
    public function getPdoDb(): PdoDb
    {
        return new PdoDb($this->connection, new Placeholders());
    }


    /**
     * Execute a raw SQL query with optional parameter binding
     * 
     * Executes the provided SQL string with optional parameter values.
     * Uses the PDO database wrapper to prepare, bind, and execute the statement.
     * 
     * @param string $sql The SQL query string to execute
     * @param array $values Optional array of parameter values to bind to the query
     * @return PdoDb Returns the PdoDb instance for further operations
     * 
     */
    public function execute(string $sql, array $values = []): PdoDb
    {
        $pdoDb = $this->getPdoDb();
        $pdoDb->query($sql)->prepareStatement()->bindValues($values)->execute();
        return $pdoDb;
    }
}
