<?php

namespace Arbor\database;


use Closure;
use Exception;
use Arbor\database\query\Builder;
use Arbor\database\connection\Connection;
use Arbor\database\connection\ConnectionPool;
use Arbor\database\utility\GrammarResolver;
use Arbor\database\query\grammar\Grammar;

/**
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
     * Base namespace for grammar classes
     * 
     * @var string
     */
    protected string $grammarNamespace = 'Arbor\\database\\query\\grammar\\';

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
     * Current active query builder instance
     * 
     * @var Builder
     */
    protected Builder $builder;


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
        $this->grammarResolver = new GrammarResolver($this->grammarNamespace);
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
    public function table(string|Closure|Builder|array $table, ?string $alias = null)
    {
        $this->connection ?? $this->withConnection();
        $this->grammar ?? $this->withGrammar();

        $this->builder = new Builder($this->grammar);

        return $this->builder->table($table, $alias);
    }


    /**
     * Helper method for holding raw SQL queries
     * 
     * @return void
     */
    public function query()
    {
        // helper method to do raw queries
        // accept string or expressions
        // prepare pdodb if not ready.
    }

    /**
     * Helper method for binding custom values to a query
     * 
     * @return void
     */
    public function values()
    {
        // helper method to bind custom values
        // accept array of values.
        // throw error if pdodb is not ready.
        // add values
    }

    /**
     * Execute the current query
     * 
     * @return void
     */
    public function execute()
    {
        // helper method to execute.
        // throw error if pdodb is not ready
        // call execute.
    }


    public function executeRaw(string $query, array $values) {}

    /**
     * Execute the current query and fetch a single row result
     * 
     * @return void
     */
    public function fetchOne()
    {
        // helper method to execute and fetch.
        // can be extended via fetchOne() and fetchAll() for convinience.
    }

    /**
     * Execute the current query and fetch all results
     * 
     * @return void
     * 
     */
    public function fetch() {}
}
