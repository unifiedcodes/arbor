<?php

namespace Arbor\database\connection;

use PDO;
use PDOException;
use Exception;
use Throwable;

/**
 * Class Connection
 *
 * Wraps a PDO instance and manages connection lifecycle for a single database.
 * Provides factory methods for creating connections from configuration or DSN strings,
 * and handles connection management including establishing, closing, and health checking.
 *
 * @package Arbor\database\connection
 */
class Connection
{
    /** @var string Database host */
    protected string $dbHost;

    /** @var string Database name */
    protected string $databaseName;

    /** @var string Database username */
    protected string $username;

    /** @var string Database password */
    protected string $password;

    /** @var string Database driver (e.g. mysql, pgsql) */
    protected string $driver;

    /** @var string Data Source Name */
    protected string $dsn;

    /**
     * Default PDO options, can be overridden via config.
     *
     * @var array<int, mixed>
     */
    private array $options = [];

    /** @var PDO|null The active PDO instance, or null if not connected */
    private ?PDO $pdo = null;


    // defaults.
    private const DEFAULT_DRIVER  = 'mysql';
    private const DEFAULT_HOST    = 'localhost';
    private const SUPPORTED_DRIVERS = ['mysql'];


    /**
     * Private constructor to enforce factory method usage.
     *
     * Initializes a new Connection instance with the provided database connection parameters.
     * The constructor is private to ensure connections are created through the factory methods
     * which provide proper validation and configuration.
     *
     * @param string $dsn The Data Source Name string
     * @param string $username Database username
     * @param string $password Database password  
     * @param string $driver Database driver (mysql, pgsql, etc.)
     * @param string $databaseName Name of the database
     * @param array<int, mixed>|null $options Optional PDO options to override defaults
     */
    private function __construct(
        string $dsn,
        string $username,
        string $password,
        string $driver,
        string $databaseName,
        ?array $options = null
    ) {
        $this->dsn      = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->driver   = $driver;
        $this->databaseName   = $databaseName;
        $this->options  = array_merge($this->defaultOptions(), $options ?? []);
    }

    /**
     * Creates a Connection instance from individual configuration parameters.
     *
     * Factory method that constructs a DSN string from the provided parameters
     * and creates a new Connection instance. Uses sensible defaults for host and driver
     * if not specified.
     *
     * @param string $username Database username
     * @param string $password Database password
     * @param string $databaseName Name of the database to connect to
     * @param string|null $host Database host (defaults to 'localhost')
     * @param string|null $driver Database driver (defaults to 'mysql')
     * @param array<int, mixed>|null $options Optional PDO options to override defaults
     * @return static New Connection instance
     */
    public static function fromConfig(
        string $username,
        string $password,
        string $databaseName,
        ?string $host = null,
        ?string $driver = null,
        ?array $options = null
    ): static {

        $driver = $driver ?? self::DEFAULT_DRIVER;
        $host   = $host   ?? self::DEFAULT_HOST;

        self::validateDriver($driver);

        $dsn = sprintf('%s:host=%s;dbname=%s', $driver, $host, $databaseName);

        return new static(
            dsn: $dsn,
            username: $username,
            password: $password,
            driver: $driver,
            databaseName: $databaseName,
            options: $options
        );
    }

    /**
     * Creates a Connection instance from a DSN string.
     *
     * Factory method that accepts a complete DSN string and parses it to extract
     * the driver and database name information needed for the Connection instance.
     *
     * @param string $dsn Complete Data Source Name string (e.g., 'mysql:host=localhost;dbname=test')
     * @param string $username Database username
     * @param string $password Database password
     * @param array<int, mixed>|null $options Optional PDO options to override defaults
     * @return static New Connection instance
     * @throws Exception If the DSN string is invalid or cannot be parsed
     */
    public static function fromDsn(
        string $dsn,
        string $username,
        string $password,
        ?array $options = null
    ): static {
        [$driver, $databaseName] = self::parseDsn($dsn);

        self::validateDriver($driver);

        return new static(
            dsn: $dsn,
            username: $username,
            password: $password,
            driver: $driver,
            databaseName: $databaseName,
            options: $options
        );
    }


    /**
     * Parses the DSN string to extract the driver and database name.
     *
     * Uses regex pattern matching to extract the driver type and database name
     * from a properly formatted DSN string. The DSN must contain a 'dbname' parameter.
     *
     * @param string $dsn The DSN string to parse
     * @return array{0: string, 1: string} Array containing [driver, databaseName]
     * @throws Exception If the DSN format is invalid or dbname parameter is missing
     */
    private static function parseDsn(string $dsn): array
    {
        if (!preg_match('/^(\w+):.*dbname=([^;]+)/', $dsn, $matches)) {
            throw new Exception("Invalid DSN: unable to extract driver and dbName.");
        }

        return [$matches[1], $matches[2]];
    }


    /**
     * Validates that the given driver is supported.
     *
     * @param string $driver
     * @throws Exception if the driver is not supported
     * @return void
     */
    private static function validateDriver(string $driver): void
    {
        if (!in_array($driver, self::SUPPORTED_DRIVERS, true)) {
            throw new Exception(
                "Unsupported driver '{$driver}'. Supported: " . implode(', ', self::SUPPORTED_DRIVERS)
            );
        }
    }

    /**
     * Returns the default PDO connection options.
     *
     * Provides sensible default options for PDO connections including:
     * - Exception mode for error handling
     * - Associative array fetch mode
     * - Disabled prepared statement emulation
     * - Non-persistent connections
     * - 30-second connection timeout
     *
     * @return array<int, mixed> Array of PDO option constants and their values
     */
    protected function defaultOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
    }


    /**
     * Establishes the PDO connection if not already connected.
     *
     * Creates a new PDO instance using the stored connection parameters and options.
     * If a connection already exists, this method returns without creating a new one.
     * Additional options can be provided to override the default configuration for this
     * specific connection attempt.
     *
     * @param array<int, mixed> $options Optional additional PDO options for this connect call
     * @return void
     * @throws Exception If PDO instantiation fails, wrapping the original PDOException
     */
    public function connect(array $options = []): void
    {
        if ($this->pdo != null && $this->pdo instanceof PDO) {
            return;
        }

        $options = array_merge($this->options, $options);

        try {
            $this->pdo = new PDO($this->dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            throw new Exception(
                sprintf('Database connection failed (%s)', $e->getMessage()),
                (int) $e->getCode(),
                $e
            );
        }
    }


    /**
     * Closes the PDO connection.
     *
     * Releases the PDO instance by setting it to null, which should trigger
     * PHP's garbage collection to clean up the database connection resources.
     *
     * @return void
     */
    public function close(): void
    {
        $this->pdo = null;
    }

    /**
     * Returns the underlying PDO instance.
     *
     * Provides access to the raw PDO object for direct database operations.
     * The connection must be established via connect() before calling this method.
     *
     * @return PDO The active PDO instance
     * @throws Exception If connect() was not called first or connection failed
     */
    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            throw new Exception('PDO instance not initialized. Call connect() first.');
        }

        return $this->pdo;
    }

    /**
     * Indicates whether the PDO connection is active.
     *
     * Checks if a PDO instance exists, but does not verify if the connection
     * is still valid or responsive. Use isAlive() for a more thorough check.
     *
     * @return bool True if a PDO instance exists, false otherwise
     */
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * Returns the configured PDO driver.
     *
     * Gets the database driver type (e.g., 'mysql', 'pgsql', 'sqlite') that was
     * specified during connection creation.
     *
     * @return string The database driver name
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Returns the DSN string in use.
     *
     * Gets the complete Data Source Name string that is used or will be used
     * to establish the PDO connection.
     *
     * @return string The DSN connection string
     */
    public function getDsn(): string
    {
        return $this->dsn;
    }


    /**
     * Checks if a connection is alive and responsive.
     *
     * Performs an actual database query ('SELECT 1') to verify that the connection
     * is not only established but also functional and able to communicate with
     * the database server. This is more thorough than isConnected() which only
     * checks if a PDO instance exists.
     *
     * @return bool True if the connection is active and responsive, false otherwise
     * @throws Exception If there's an error getting the PDO instance
     */
    public function isAlive(): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        try {
            /** @var PDO $pdo */
            $pdo = $this->getPdo();
            $pdo->query('SELECT 1');
            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
