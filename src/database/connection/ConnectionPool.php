<?php

namespace Arbor\database\connection;


use Exception;
use Arbor\attributes\ConfigValue;

/**
 * Class ConnectionPool
 *
 * Manages a pool of named database connections with retry logic, idle tracking, and eviction.
 * 
 * @package Arbor\database\connection
 * 
 */
class ConnectionPool
{
    /** @var array<string, Connection> */
    protected array $connections = [];

    /**
     * @var array<string, array{isIdle: bool, maxRetries: int, retryDelay: int}>
     */
    protected array $attributes = [];

    protected int $defaultMaxConnections;
    protected int $defaultMaxRetries;
    protected int $defaultRetryDelay;

    /**
     * ConnectionPool constructor.
     *
     * @param int|null $maxConnections
     * @param int|null $maxRetries
     * @param int|null $retryDelay
     * @param array<string, array<string, mixed>>|null $dbConnections
     */
    public function __construct(
        #[ConfigValue('db.pool.maxConnections')]
        ?int $maxConnections = null,

        #[ConfigValue('db.pool.maxRetries')]
        ?int $maxRetries = null,

        #[ConfigValue('db.pool.retryDelay')]
        ?int $retryDelay = null,

        #[ConfigValue('db.connections')]
        ?array $dbConnections = null
    ) {
        $this->defaultMaxConnections = $maxConnections ?? 10;
        $this->defaultMaxRetries     = $maxRetries     ?? 10;
        $this->defaultRetryDelay     = $retryDelay     ?? 1000;

        $this->addConnectionsByConfig($dbConnections ?? []);
    }

    /**
     * Adds multiple connections from configuration.
     *
     * @param array<string, array<string, mixed>> $dbConnections
     * @return void
     */
    protected function addConnectionsByConfig(array $dbConnections): void
    {
        foreach ($dbConnections as $name => $config) {

            $connection = $this->createConnectionObject($config);

            $this->addConnection(
                $name,
                $connection,
                $config['maxRetries'] ?? null,
                $config['retryDelay'] ?? null
            );
        }
    }


    protected function createConnectionObject(array $config): Connection
    {
        // required config keys.
        $username = $config['username'] ?? throw new \InvalidArgumentException('Missing "username" in DB config.');
        $password = $config['password'] ?? throw new \InvalidArgumentException('Missing "password" in DB config.');
        $options  = $config['options']  ?? null;


        // has dsn string
        if (!empty($config['dsn'])) {
            // Use DSN directly
            return Connection::fromDsn(
                dsn: $config['dsn'],
                username: $username,
                password: $password,
                options: $options
            );
        }

        // making db name key mandatory when dsn is provided.
        $databaseName = $config['databaseName'] ?? throw new \InvalidArgumentException('Missing "databaseName" in DB config.');


        // building with config.
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
     * Adds a connection to the pool.
     *
     * @param string $name
     * @param Connection $connection
     * @param int|null $maxRetries
     * @param int|null $retryDelay
     * @return void
     * @throws Exception
     */
    public function addConnection(
        string $name,
        Connection $connection,
        ?int $maxRetries = null,
        ?int $retryDelay = null
    ): void {

        if (count($this->connections) >= $this->defaultMaxConnections) {
            $this->evictIdleConnections();

            if (count($this->connections) >= $this->defaultMaxConnections) {
                throw new Exception("Connection pool is full, couldn't add connection {$name} in pool");
            }
        }

        if (isset($this->connections[$name])) {
            throw new Exception("Connection with name '$name' already exists");
        }

        $this->connections[$name] = $connection;

        $this->attributes[$name] = [
            'isIdle' => true,
            'maxRetries' => $maxRetries ?? $this->defaultMaxRetries,
            'retryDelay' => $retryDelay ?? $this->defaultRetryDelay,
        ];
    }

    /**
     * Retrieves a connection from the pool without acquiring it.
     *
     * @param string $name
     * @return Connection
     * @throws Exception
     */
    public function getConnection(string $name): Connection
    {
        if (!isset($this->connections[$name])) {
            throw new Exception("Connection '$name' does not exist in the pool");
        }

        return $this->connections[$name];
    }

    /**
     * Acquires a connection (sets it as non-idle) and attempts connection with retries.
     *
     * @param string $name
     * @return Connection
     * @throws Exception
     */
    public function acquireConnection(string $name): Connection
    {
        if (!isset($this->connections[$name])) {
            throw new Exception("Connection '$name' does not exist in the pool");
        }

        $connection = $this->connections[$name];
        $this->attributes[$name]['isIdle'] = false;

        $maxRetries = $this->attributes[$name]['maxRetries'];
        $retryDelay = $this->attributes[$name]['retryDelay'];

        $attempt = 0;

        while (!$connection->isConnected()) {
            try {
                $connection->connect();
            } catch (\Throwable $e) {
                $attempt++;
                if ($attempt >= $maxRetries) {
                    throw new Exception("Failed to connect to '$name' after {$maxRetries} attempts", 0, $e);
                }
                usleep($retryDelay);
            }
        }

        return $connection;
    }

    /**
     * Marks a connection as idle.
     *
     * @param string $name
     * @return void
     * @throws Exception
     */
    public function releaseConnection(string $name): void
    {
        if (!isset($this->connections[$name])) {
            throw new Exception("Connection '$name' does not exist in the pool");
        }

        $this->attributes[$name]['isIdle'] = true;
    }

    /**
     * Executes a callback with an acquired connection.
     *
     * @param string $name
     * @param callable(Connection): mixed $callback
     * @return mixed
     * @throws Exception
     */
    public function withConnection(string $name, callable $callback): mixed
    {
        $connection = $this->acquireConnection($name);

        try {
            return $callback($connection);
        } finally {
            $this->releaseConnection($name);
        }
    }

    /**
     * Evicts the first idle connection from the pool.
     *
     * @return bool True if a connection was evicted.
     */
    public function evictIdleConnections(): bool
    {
        foreach ($this->connections as $name => $connection) {
            if ($this->attributes[$name]['isIdle'] === true) {
                $this->destroyConnection($name);
                return true;
            }
        }

        return false;
    }

    /**
     * Removes a connection from the pool and closes it.
     *
     * @param string $name
     * @return void
     * @throws Exception
     */
    public function destroyConnection(string $name): void
    {
        if (!isset($this->connections[$name])) {
            throw new Exception("Connection '$name' does not exist in the pool");
        }

        $this->connections[$name]->close();
        unset($this->connections[$name], $this->attributes[$name]);
    }

    /**
     * Checks if the connection with the given name exists in the pool.
     *
     * @param string $name
     * @return bool
     */
    public function hasConnection(string $name): bool
    {
        return isset($this->connections[$name]);
    }

    /**
     * Checks whether a given connection is idle.
     *
     * @param string $name
     * @return bool
     * @throws Exception
     */
    public function isConnectionIdle(string $name): bool
    {
        if (!isset($this->attributes[$name])) {
            throw new Exception("Connection '$name' does not exist in the pool");
        }

        return $this->attributes[$name]['isIdle'];
    }

    /**
     * Gets the total number of connections in the pool.
     *
     * @return int
     */
    public function getConnectionCount(): int
    {
        return count($this->connections);
    }

    /**
     * Closes all connections and clears the pool.
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
        $this->attributes = [];
    }

    /**
     * Returns all connection names in the pool.
     *
     * @return string[]
     */
    public function getConnectionNames(): array
    {
        return array_keys($this->connections);
    }

    /**
     * Checks if a connection is alive (not stale).
     *
     * @param string $name
     * @return bool
     * @throws Exception
     */
    public function isConnectionAlive(string $name): bool
    {
        $connection = $this->getConnection($name);
        return $connection->isAlive();
    }
}
