<?php

namespace Arbor\database\connection;

use Throwable;
use Exception;
use InvalidArgumentException;
use Arbor\attributes\ConfigValue;

/**
 * Class ConnectionPool
 *
 * Manages a pool of named database connections with retry logic.
 * 
 * @package Arbor\database\connection
 * 
 */
class ConnectionPool
{
    /** @var array<string, Connection> Active database connections indexed by name */
    protected array $connections = [];

    /** @var array<string, array<string, mixed>> Configuration arrays for lazy connection creation indexed by name */
    protected array $configs = [];

    /** @var int Default maximum number of connection retry attempts */
    protected int $defaultMaxRetries;

    /** @var int Default delay between retry attempts in milliseconds */
    protected int $defaultRetryDelay;

    /**
     * ConnectionPool constructor.
     *
     * @param array|null $dbConnections Array of database connection configurations
     * @param int|null $maxRetries Default maximum retry attempts for connections
     * @param int|null $retryDelay Default retry delay in milliseconds
     */
    public function __construct(
        #[ConfigValue('database.connections')]
        ?array $dbConnections = null,

        #[ConfigValue('database.maxRetries')]
        ?int $maxRetries = null,

        #[ConfigValue('database.retryDelay')]
        ?int $retryDelay = null
    ) {
        $this->defaultMaxRetries = $maxRetries ?? 3;
        $this->defaultRetryDelay = $retryDelay ?? 1000;

        if ($dbConnections) {
            foreach ($dbConnections as $name => $config) {
                $this->registerConnection($name, $config);
            }
        }
    }

    /**
     * Add connection config for lazy creation.
     *
     * @param string $name The name identifier for the connection
     * @param array $config Configuration array containing connection parameters
     * @return void
     */
    public function registerConnection(string $name, array $config): void
    {
        $this->configs[$name] = $config;
    }

    /**
     * Add a pre-made connection instance.
     *
     * @param string $name The name identifier for the connection
     * @param Connection $connection Pre-existing connection instance
     * @return void
     */
    public function addConnection(string $name, Connection $connection): void
    {
        $this->connections[$name] = $connection;
    }

    /**
     * Acquire a database connection by name, creating it if necessary with retry logic.
     *
     * @param string $name The name of the connection to acquire
     * @return Connection The requested database connection
     * @throws Exception If no configuration is found or connection fails after max retries
     */
    public function acquireConnection(string $name): Connection
    {
        // Return existing connection
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        // Make sure config exists
        if (!isset($this->configs[$name])) {
            throw new Exception("No configuration found for connection '{$name}'");
        }

        $config = $this->configs[$name];
        $maxRetries = $config['maxRetries'] ?? $this->defaultMaxRetries;
        $retryDelay = $config['retryDelay'] ?? $this->defaultRetryDelay;

        $connection = $this->createConnection($config);

        $attempt = 0;
        while (!$connection->isConnected()) {
            try {
                $connection->connect();
            } catch (Throwable $e) {
                $attempt++;
                if ($attempt >= $maxRetries) {
                    throw new Exception(
                        "Failed to connect to '{$name}' after {$maxRetries} attempts",
                        0,
                        $e
                    );
                }
                usleep($retryDelay * 1000); // convert ms to microseconds
            }
        }

        $this->connections[$name] = $connection;

        return $connection;
    }

    /**
     * Create a new Connection instance from configuration array.
     *
     * @param array $config Configuration array containing connection parameters
     * @return Connection New connection instance
     * @throws InvalidArgumentException If required configuration parameters are missing
     */
    protected function createConnection(array $config): Connection
    {
        $username = $config['username'] ?? throw new InvalidArgumentException('Missing "username"');
        $password = $config['password'] ?? throw new InvalidArgumentException('Missing "password"');
        $options  = $config['options'] ?? null;

        if (!empty($config['dsn'])) {
            return Connection::fromDsn(
                dsn: $config['dsn'],
                username: $username,
                password: $password,
                options: $options
            );
        }

        $databaseName = $config['databaseName'] ?? throw new InvalidArgumentException('Missing "databaseName"');

        return Connection::fromConfig(
            username: $username,
            password: $password,
            databaseName: $databaseName,
            host: $config['host'] ?? null,
            driver: $config['driver'] ?? null,
            options: $options
        );
    }

    /**
     * Execute a callback with a specific database connection.
     *
     * @param string $name The name of the connection to use
     * @param callable $callback The callback function to execute with the connection
     * @return mixed The return value of the callback function
     * @throws Exception If the connection cannot be acquired
     */
    public function withConnection(string $name, callable $callback): mixed
    {
        $connection = $this->acquireConnection($name);
        return $callback($connection);
    }

    /**
     * Destroy a specific connection by name, closing it and removing from the pool.
     *
     * @param string $name The name of the connection to destroy
     * @return void
     * @throws Exception If the connection does not exist in the pool
     */
    public function destroyConnection(string $name): void
    {
        if (!isset($this->connections[$name])) {
            throw new Exception("Connection '$name' does not exist in the pool");
        }

        $this->connections[$name]->close();
        unset($this->connections[$name]);
    }

    /**
     * Check if a connection exists in the pool (regardless of its state).
     *
     * @param string $name The name of the connection to check
     * @return bool True if the connection exists in the pool, false otherwise
     */
    public function hasConnection(string $name): bool
    {
        return isset($this->configs[$name]);
    }

    /**
     * Close all active connections and clear the connection pool.
     *
     * @return void
     */
    public function closeAll(): void
    {
        foreach ($this->connections as $connection) {
            if ($connection->isConnected()) {
                $connection->close();
            }
        }

        $this->connections = [];
    }

    /**
     * Get an array of all connection names that can be made available by pool.
     *
     * @return array Array of connection names (string keys)
     */
    public function getConnectionNames(): array
    {
        return array_keys($this->configs);
    }

    /**
     * Check if a specific connection is alive and responsive.
     *
     * @param string $name The name of the connection to check
     * @return bool True if the connection is alive, false otherwise
     * @throws Exception If the connection is not found in the pool
     */
    public function isConnectionAlive(string $name): bool
    {
        if (!isset($this->connections[$name])) {
            throw new Exception("Connection '$name' not found");
        }

        $connection = $this->connections[$name];
        return $connection->isAlive();
    }
}
