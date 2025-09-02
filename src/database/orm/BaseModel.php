<?php

namespace Arbor\database\orm;

use ArrayAccess;
use JsonSerializable;
use RuntimeException;
use Arbor\database\Database;
use Arbor\database\DatabaseResolver;
use Arbor\database\orm\AttributesTrait;
use Arbor\database\orm\ModelQuery;


abstract class BaseModel implements ArrayAccess, JsonSerializable
{
    use AttributesTrait;

    private static ?DatabaseResolver $databaseResolver = null;
    protected static ?string $connection = null;
    protected static ?string $tableName = null;
    protected static string $primaryKey = 'id';
    protected bool $exists = false;


    public function __construct(array $attributes = [], bool $exists = false)
    {
        $this->fill($attributes);
        $this->syncOriginal();
        $this->exists($exists);
    }

    // -----------------------
    // Database resolver
    // -----------------------

    public static function setResolver(DatabaseResolver $databaseResolver): void
    {
        self::$databaseResolver = $databaseResolver;
    }


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


    public static function setConnection($name)
    {
        static::$connection = $name;
    }


    public static function resetConnection(): void
    {
        static::$connection = null;
    }

    // -----------------------
    // Query builder access
    // -----------------------

    public static function query(?string $connectionName = null): ModelQuery
    {
        return new ModelQuery(static::getDatabase($connectionName), static::class);
    }


    public static function on(?string $connectionName = null): ModelQuery
    {
        return static::query($connectionName);
    }


    public static function __callStatic($name, $arguments): mixed
    {
        return static::query()->$name(...$arguments);
    }

    // -----------------------
    // Table & primary key
    // -----------------------

    public static function getTableName(): string
    {
        if (!static::$tableName) {
            $modelName = self::class;
            throw new RuntimeException("Model '{$modelName}' does not define its table name.");
        }

        return static::$tableName;
    }


    public static function getPrimaryKey(): string
    {
        return static::$primaryKey;
    }


    public function getPrimaryKeyValue()
    {
        return $this->getAttribute(static::getPrimaryKey());
    }


    public function exists(bool $is): void
    {
        $this->exists = $is;
    }
}
