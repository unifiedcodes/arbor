<?php

namespace Arbor\database\orm;

use ArrayAccess;
use JsonSerializable;
use RuntimeException;
use Arbor\database\Database;
use Arbor\database\DatabaseResolver;
use Arbor\database\orm\AttributesTrait;
use Arbor\database\orm\ModelQuery;

/**
 * Base Model Class for ORM
 * 
 * This abstract class provides the foundation for all ORM models in the Arbor framework.
 * It implements ArrayAccess and JsonSerializable interfaces, providing array-like access
 * to model attributes and JSON serialization capabilities.
 * 
 * The class manages database connections through a DatabaseResolver, handles model attributes
 * using the AttributesTrait, and provides query builder functionality through ModelQuery.
 * 
 * @package Arbor\database\orm
 * @abstract
 * @implements ArrayAccess<string, mixed>
 * @implements JsonSerializable
 * 
 */
abstract class BaseModel implements ArrayAccess, JsonSerializable
{
    use AttributesTrait;

    /**
     * The database resolver instance used to manage database connections
     * 
     * @var DatabaseResolver|null
     */
    private static ?DatabaseResolver $databaseResolver = null;

    /**
     * The database connection name to use for this model
     * 
     * @var string|null
     */
    protected static ?string $connection = null;

    /**
     * The table name associated with this model
     * 
     * @var string|null
     */
    protected static ?string $tableName = null;

    /**
     * The primary key column name for this model
     * 
     * @var string
     */
    protected static string $primaryKey = 'id';

    /**
     * Indicates if the model exists in the database
     * 
     * @var bool
     */
    protected bool $exists = false;

    /**
     * Create a new model instance
     * 
     * @param array<string, mixed> $attributes Initial attributes to set on the model
     * @param bool $exists Whether the model exists in the database
     */
    public function __construct(array $attributes = [], bool $exists = false)
    {
        $this->fill($attributes);
        $this->syncOriginal();
        $this->exists($exists);
    }

    // -----------------------
    // Database resolver
    // -----------------------

    /**
     * Set the database resolver for all models
     * 
     * @param DatabaseResolver $databaseResolver The database resolver instance
     * @return void
     */
    public static function setResolver(DatabaseResolver $databaseResolver): void
    {
        self::$databaseResolver = $databaseResolver;
    }

    /**
     * Get a database connection instance
     * 
     * Retrieves a database connection using the provided name, or falls back to
     * the model's configured connection, or the resolver's default connection.
     * 
     * @param string|null $name The connection name to retrieve
     * @return Database The database connection instance
     * @throws RuntimeException If no database resolver is set
     */
    public static function getDatabase(?string $name = null): Database
    {
        if (!static::$databaseResolver) {
            $modelName = self::class;
            throw new RuntimeException("Cannot initiate Model '{$modelName}' — no DatabaseResolver set.");
        }

        $connName = $name
            ?? static::$connection
            ?? static::$databaseResolver->getDefault();

        return static::$databaseResolver->get($connName);
    }

    /**
     * Set the database connection name for this model
     * 
     * @param string $name The connection name to use
     * @return void
     */
    public static function setConnection($name)
    {
        static::$connection = $name;
    }

    /**
     * Reset the database connection to use the default
     * 
     * @return void
     */
    public static function resetConnection(): void
    {
        static::$connection = null;
    }

    // -----------------------
    // Query builder access
    // -----------------------

    /**
     * Create a new query builder instance for this model
     * 
     * @param string|null $connectionName The database connection name to use
     * @return ModelQuery A new query builder instance
     */
    public static function query(?string $connectionName = null): ModelQuery
    {
        return new ModelQuery(static::getDatabase($connectionName), static::class);
    }

    /**
     * Alias for query() method to specify a database connection
     * 
     * @param string|null $connectionName The database connection name to use
     * @return ModelQuery A new query builder instance
     */
    public static function on(?string $connectionName = null): ModelQuery
    {
        return static::query($connectionName);
    }

    /**
     * Dynamically handle static method calls
     * 
     * Forwards static method calls to the query builder, allowing methods like
     * Model::where(), Model::find(), etc. to work directly on the model class.
     * 
     * @param string $name The method name being called
     * @param array<mixed> $arguments The arguments passed to the method
     * @return mixed The result from the query builder method
     */
    public static function __callStatic($name, $arguments): mixed
    {
        return static::query()->$name(...$arguments);
    }

    // -----------------------
    // Table & primary key
    // -----------------------

    /**
     * Get the table name for this model
     * 
     * @return string The table name
     * @throws RuntimeException If no table name is defined for the model
     */
    public static function getTableName(): string
    {
        if (!static::$tableName) {
            $modelName = self::class;
            throw new RuntimeException("Model '{$modelName}' does not define its table name.");
        }

        return static::$tableName;
    }

    /**
     * Get the primary key column name for this model
     * 
     * @return string The primary key column name
     */
    public static function getPrimaryKey(): string
    {
        return static::$primaryKey;
    }

    /**
     * Get the value of the primary key for this model instance
     * 
     * @return mixed The primary key value
     */
    public function getPrimaryKeyValue()
    {
        return $this->getAttribute(static::getPrimaryKey());
    }

    /**
     * Set whether this model exists in the database
     * 
     * @param bool $is True if the model exists in the database, false otherwise
     * @return void
     */
    public function exists(bool $is): void
    {
        $this->exists = $is;
    }
}
