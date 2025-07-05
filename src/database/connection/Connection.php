<?php

namespace Arbor\database\connection;

use PDO;
use PDOException;
use Exception;

/**
 * Class Connection
 *
 * Wraps a PDO instance and manages connection lifecycle for a single database.
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

    public static function fromDsn(
        string $dsn,
        string $username,
        string $password,
        ?array $options = null
    ): static {
        [$driver, $databaseName] = self::parseDsn($dsn);

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
     * Parses the DSN string to extract the driver and dbName
     *
     * @throws Exception if dbname is not present
     */
    private static function parseDsn(string $dsn): array
    {
        if (!preg_match('/^(\w+):.*dbname=([^;]+)/', $dsn, $matches)) {
            throw new Exception("Invalid DSN: unable to extract driver and dbName.");
        }

        return [$matches[1], $matches[2]];
    }

    /**
     * default pdo connection options
     *
     * @return array of default options
     */
    protected function defaultOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::ATTR_TIMEOUT            => 30,
        ];
    }


    /**
     * Establishes the PDO connection if not already connected.
     *
     * @param array<int, mixed> $options Optional additional PDO options for this connect call
     * @return void
     * @throws Exception If PDO instantiation fails
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
     * @return void
     */
    public function close(): void
    {
        $this->pdo = null;
    }

    /**
     * Returns the underlying PDO instance.
     *
     * @return PDO
     * @throws Exception If `connect()` was not called first
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
     * @return bool True if connected
     */
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * Returns the configured PDO driver.
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Returns the DSN string in use.
     *
     * @return string
     */
    public function getDsn(): string
    {
        return $this->dsn;
    }


    /**
     * Checks if a connection is alive (not stale).
     *
     * @param string $name
     * @return bool
     * @throws Exception
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
        } catch (\Throwable) {
            return false;
        }
    }
}
