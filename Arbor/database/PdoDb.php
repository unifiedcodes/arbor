<?php

namespace Arbor\database;

use PDO;
use Exception;
use PDOStatement;
use Arbor\database\utility\Placeholders;
use Arbor\database\connection\Connection;

/**
 * PdoDb - Database abstraction layer for PDO connections
 * 
 * This class provides a simplified interface for working with PDO databases,
 * handling connection management, query preparation, parameter binding,
 * and result fetching.
 * 
 * @package Arbor\database
 * 
 */
class PdoDb
{
    protected PDO $pdo;
    protected Connection $connection;

    /**
     * @var string|null SQL query string
     */
    protected ?string $sql = null;

    /**
     * @var PDOStatement|null PDO statement instance
     */
    protected ?PDOStatement $statement = null;

    /**
     * @var bool|null Result after execution of query
     */
    protected ?bool $result = null;


    /**
     * Default fetch mode for query results
     * 
     * @var int PDO fetch mode constant
     */
    private int $fetchMode = PDO::FETCH_ASSOC;

    /**
     * Placeholders handler for query parameter binding
     */
    protected Placeholders $placeholders;


    public function __construct(Connection $connection, Placeholders $placeholder)
    {
        $this->connection = $connection;
        $this->pdo = $connection->getPdo();
        $this->placeholders = $placeholder;
    }

    /**
     * Reset the current statement, result, and SQL query
     * 
     * @return void
     */
    public function reset(): void
    {
        // clean up statement, result, sql
        $this->statement = null;
        $this->result = null;
        $this->sql = null;


        // Reset placeholders state
        $this->placeholders->reset();
    }


    // --- GETTERS

    /**
     * Get the database type (mysql, sqlite, pgsql, etc.)
     *
     * @return string The database type
     */
    public function getDatabaseType(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }


    /**
     * Returns the corresponding PDO constant for a given type.
     *
     * @param string $type A short code or full type name
     * @return int The PDO constant for the specified data type
     */
    protected static function getPdoDataType(?string $type = null): int
    {
        static $dataTypesMap = [
            'default' => PDO::PARAM_STR,
            's'       => PDO::PARAM_STR,
            'null'    => PDO::PARAM_NULL,
            'n'       => PDO::PARAM_NULL,
            'integer' => PDO::PARAM_INT,
            'i'       => PDO::PARAM_INT,
            'string'  => PDO::PARAM_STR,
            'blob'    => PDO::PARAM_LOB,
            'bb'      => PDO::PARAM_LOB,
            'boolean' => PDO::PARAM_BOOL,
            'b'       => PDO::PARAM_BOOL,
        ];

        return $dataTypesMap[strtolower($type)] ?? $dataTypesMap['default'];
    }

    //--- SQL TRANSACTION METHODS

    /**
     * Begin a database transaction
     *
     * @return bool True on success, false on failure
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit the current transaction
     *
     * @return bool True on success, false on failure
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Roll back the current transaction
     *
     * @return bool True on success, false on failure
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Check if a transaction is currently active
     *
     * @return bool True if a transaction is active, false otherwise
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }


    /**
     * Execute a callback within a transaction
     *
     * @param callable $callback Function to execute within the transaction
     * @return mixed The callback's return value
     * @throws Exception When an error occurs during the transaction
     */
    public function transaction(callable $callback): mixed
    {
        try {
            // Begin transaction
            $this->beginTransaction();

            // Execute the callback
            $result = $callback($this);

            // Commit if everything went well
            $this->commit();

            return $result;
        } catch (Exception $e) {
            // Rollback on error
            if ($this->inTransaction()) {
                $this->rollBack();
            }

            throw $e;
        }
    }


    /**
     * Execute a transaction safely, returning a success status instead of throwing
     *
     * @param callable $callback Function to execute within the transaction
     * @param mixed &$result Variable to store the callback's return value
     * @param string &$error Variable to store error message if transaction fails
     * @return bool True if transaction succeeded, false otherwise
     */
    public function safeTransaction(callable $callback, mixed &$result = null, string &$error = ''): bool
    {
        try {
            $result = $this->transaction($callback);
            return true;
        } catch (Exception $e) {
            $error = $e->getMessage();
            return false;
        }
    }

    /**
     * Prepare the SQL statement for execution
     * 
     * @return static
     * @throws Exception When SQL query is empty or not set
     */
    public function prepareStatement(): static
    {
        if (!$this->sql) {
            throw new Exception("Sql is empty or not set yet");
        }

        $this->statement = $this->pdo->prepare($this->sql);

        return $this;
    }

    /**
     * Ensure the statement is a valid PDOStatement
     * 
     * @return void
     * @throws Exception When statement is invalid
     */
    protected function ensureValidStatement(): void
    {
        if (!$this->statement || !$this->statement instanceof PDOStatement) {
            throw new Exception('Binding values require a valid PDOStatement, invalid or empty statement found!');
        }
    }

    /**
     * Execute the prepared statement
     * 
     * @return static
     * @throws Exception When statement is not prepared before execution
     */
    public function execute(): static
    {
        $this->ensureValidStatement();
        $this->result = $this->statement->execute();

        return $this;
    }

    //--- Result Fetchers

    /**
     * Fetch all rows from the result set
     * 
     * @param int|null $mode PDO fetch mode (defaults to class fetch mode)
     * @return array<int, mixed> All rows from the result set
     */
    public function fetchAll(?int $mode = null): array
    {
        $fetchMode = $mode ?? $this->fetchMode;
        return $this->statement->fetchAll($fetchMode);
    }

    /**
     * Fetch a single row from the result set
     * 
     * @param int|null $mode PDO fetch mode (defaults to class fetch mode)
     * @return mixed Single row from the result set or false if no rows
     */
    public function fetchOne(?int $mode = null): mixed
    {
        $fetchMode = $mode ?? $this->fetchMode;
        return $this->statement->fetch($fetchMode);
    }

    /**
     * Fetch a single column from the next row in the result set
     * 
     * @return mixed Value of the column or false if no rows
     */
    public function fetchColumn(): mixed
    {
        return $this->statement->fetchColumn();
    }

    /**
     * Get the number of rows affected by the last SQL statement
     * 
     * @return int Number of affected rows
     */
    public function rowCount(): int
    {
        return $this->statement->rowCount();
    }

    /**
     * Get the ID of the last inserted row
     * 
     * @return string Last insert ID
     */
    public function getInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    //--- Query Builders


    /**
     * Prepare a SQL query
     * 
     * @param string $query SQL query with placeholders
     * @return static Current instance for method chaining
     */
    public function query(string $query): static
    {
        $this->reset();

        // Parse the query and collect placeholders, type hints and get a cleaned sql string
        $this->sql = $this->placeholders->parseSql($query);

        return $this;
    }


    /**
     * Bind values to statement placeholders
     * 
     * @param array<string|int, mixed> $values Values to bind to placeholders
     * @return static
     * @throws Exception When binding fails or validation error occurs
     */
    public function bindValues(array $values): static
    {
        // Ensure $this->statement is valid PDOStatement
        $this->ensureValidStatement();

        // Normalize values with their types
        $values = $this->normalizeValues($values);

        // Validate values against placeholders
        $this->validateValues($values);


        foreach ($values as $key => $value) {
            // Bind the value with the proper placeholder and type
            $this->statement->bindValue($key, $value['value'], $value['type']);
        }


        return $this;
    }

    /**
     * Validate that provided values match expected placeholders
     * 
     * @param array<string|int, mixed> $values Values to validate
     * @return void
     * @throws Exception When validation fails
     */
    protected function validateValues(array $values): void
    {
        // check if count is correct.
        $valueCount = count($values);
        $placeholdersCount = count($this->placeholders->getPlaceholders());

        if ($valueCount !== $placeholdersCount) {
            throw new Exception("Last query expects $placeholdersCount values, but $valueCount supplied.");
        }

        $placeholders = $this->placeholders->getPlaceholders(); // array: key => type
        $placeholderType = $this->placeholders->getPlaceholderType(); // 'named' or 'positional'

        // check if keys are correct type.
        foreach ($values as $key => $value) {

            if ($placeholderType === 'named') {
                if (!is_string($key)) {
                    throw new Exception("Keys for values supplied with a named placeholder query must be of string type");
                }

                if (!isset($placeholders[$key])) {
                    // this defence to be removed if we need to support a placeholder parsing bypass.
                    throw new Exception("There is no such placeholder in query with name: '{$key}'");
                }
            }

            if ($placeholderType === 'positional' && !is_int($key)) {
                throw new Exception("Keys for values supplied with a positional placeholder query must be integer type");
            }
        }
    }

    /**
     * Normalize values by extracting types and preparing for binding
     * 
     * @param array<string|int, mixed> $values Values to normalize
     * @return array<string|int, array{value: mixed, type: int|null}> Normalized values with types
     */
    protected function normalizeValues(array $values): array
    {
        $normalized = [];
        $i = 1;

        $placeholders = $this->placeholders->getPlaceholders(); // array: key => type
        $placeholderType = $this->placeholders->getPlaceholderType(); // 'named' or 'positional'

        foreach ($values as $key => $value) {
            $parts = explode('@', $key, 2);
            $name = $parts[0];
            $typeHintFromKey = $parts[1] ?? null;

            // If value is array and has 'value', trust that format
            if (is_array($value) && array_key_exists('value', $value)) {
                $valueData = $value['value'];
                $type = $value['type'] ?? null;
            } else {
                $valueData = $value;
                $type = $typeHintFromKey;
            }

            // Apply fallback to $placeholders if type is still not set
            if ($type === null) {
                if ($placeholderType === 'named' && isset($placeholders[$name])) {
                    $type = $placeholders[$name];
                } elseif ($placeholderType === 'positional' && isset($placeholders[$i])) {
                    $type = $placeholders[$i];
                }
            }

            $item = [
                'value' => $valueData,
                'type'  => self::getPdoDataType($type) ?? null,
            ];

            if ($placeholderType === 'positional') {
                $normalized[$i++] = $item;
            } else {
                $normalized[$name] = $item;
            }
        }

        return $normalized;
    }
}
