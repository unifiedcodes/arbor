<?php

namespace Arbor\database;

use Arbor\database\connection\ConnectionPool;
use InvalidArgumentException;

/**
 * DatabaseResolver manages database connections and provides a registry for database instances.
 * 
 * This class acts as a factory and registry for Database instances, allowing you to:
 * - Register database connections by name
 * - Create and cache Database instances
 * - Manage connection configurations
 * 
 * @package Arbor\database
 */
class DatabaseResolver
{
    /**
     * The connection pool used to manage database connections.
     */
    protected ConnectionPool $pool;

    /**
     * Registry can hold:
     *  - name => connectionName (string)
     *  - name => config array
     * 
     * @var array<string, string|array<string, mixed>>
     */
    protected array $registry = [];

    /**
     * Cache of created Database instances indexed by name.
     * 
     * @var array<string, Database>
     */
    protected array $instances = [];

    /**
     * The default database alias to use when no explicit alias is provided.
     */
    protected ?string $default = null;


    /**
     * Initialize the DatabaseResolver with a connection pool.
     * 
     * @param ConnectionPool $pool The connection pool to use for managing database connections
     */
    public function __construct(ConnectionPool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Set the global default database alias.
     *
     * @param string $name The registered alias name
     * @return void
     *
     * @throws InvalidArgumentException If alias not registered
     */
    public function setDefault(string $name): void
    {
        if (!isset($this->registry[$name])) {
            throw new InvalidArgumentException("Cannot set default: alias [$name] not registered.");
        }

        $this->default = $name;
    }

    /**
     * Get the global default database alias.
     *
     * @return string|null The default alias or null if not set
     */
    public function getDefault(): ?string
    {
        return $this->default;
    }

    /**
     * Register all pool connections into registry (name = connectionName).
     * 
     * This method automatically registers all existing connections from the pool
     * into the resolver's registry, using the connection name as both the key
     * and value in the registry.
     * 
     * @return void
     */
    public function byConfig(): void
    {
        foreach ($this->pool->getConnectionNames() as $name => $connectionName) {

            if (is_int($name)) {
                $name = $connectionName;
            }

            $this->set($name, $connectionName);
        }
    }

    /**
     * Register an entry in registry.
     * 
     * This method allows you to register a database connection either by:
     * 1. Referencing an existing connection name from the pool
     * 2. Providing a configuration array to create a new connection
     * 
     * @param string $name Logical name used by developer to reference this database
     * @param string|array<string, mixed> $connection Either an existing connection name, or config array
     * 
     * @throws InvalidArgumentException If trying to add a connection that already exists in the pool
     * 
     * @return void
     */
    public function set(string $name, string|array $connection): void
    {
        if (is_array($connection)) {
            if ($this->pool->hasConnection($name)) {
                throw new InvalidArgumentException("Cannot add connection: '{$name}', already exists in connection pool.");
            }

            // registering a new connection with provided name and config array.
            $this->pool->registerConnection($name, $connection);

            // using name as connectionName.
            $connection = $name;
        }

        $this->registry[$name] = $connection;
    }

    /**
     * Get a Database instance (lazy-loaded).
     * 
     * This method returns a Database instance for the given name. If the instance
     * doesn't exist yet, it will be created and cached for future use.
     * 
     * @param string $name The registered name of the database connection
     * 
     * @return Database The Database instance for the specified connection
     * 
     * @throws InvalidArgumentException If the database alias is not registered
     */
    public function get(?string $name = null): Database
    {

        $name = $name ?? $this->getDefault();

        if ($name === null) {
            throw new InvalidArgumentException("No database alias specified and no default database configured.");
        }

        // Already built?
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        if (!isset($this->registry[$name])) {
            throw new InvalidArgumentException("Database alias [$name] not registered.");
        }

        $connectionName = $this->registry[$name];

        // Build Database object
        $db = (new Database($this->pool))
            ->withConnection($connectionName);

        return $this->instances[$name] = $db;
    }

    /**
     * Check if a database connection is registered.
     * 
     * @param string $name The name to check for in the registry
     * 
     * @return bool True if the name exists in the registry, false otherwise
     */
    public function has(string $name): bool
    {
        return isset($this->registry[$name]);
    }
}
