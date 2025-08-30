<?php

namespace Arbor\database\orm;


use ArrayAccess;
use JsonSerializable;
use RuntimeException;
use Arbor\database\Database;
use Arbor\database\DatabaseResolver;
use Arbor\database\orm\AttributesTrait;


abstract class Model implements ArrayAccess, JsonSerializable
{
    use AttributesTrait;


    private static ?DatabaseResolver $databaseResolver = null;
    protected static ?string $connection = null;
    protected static ?string $tableName = null;
    protected bool $exists = false;


    public function __construct(array $attributes = [], bool $exists = false)
    {
        $this->fill($attributes);
        $this->syncOriginal();
        $this->exists = $exists;
    }


    // Must be injected by provider, it binds a singleton DatabaseResolver instance with all Models.
    // so that models can ask Database from DatabaseResolver when they need.
    public static function setResolver(DatabaseResolver $databaseResolver): void
    {
        self::$databaseResolver = $databaseResolver;
    }


    // ask database resolver to give a database instance.
    public static function getDatabase(?string $name = null): Database
    {
        if (!static::$databaseResolver) {
            $modelName = self::class;
            throw new RuntimeException("Cannot Initiate Model '{$modelName}' No DatabaseResolver set for ORM Model.");
        }

        $connName = $name
            ?? static::$connection
            ?? static::$databaseResolver->getDefault();

        // Global App level default.
        return static::$databaseResolver->get($connName);
    }


    // set default connection for each model, usually set by a provider.
    public static function setConnection($name)
    {
        static::$connection = $name;
    }


    public static function resetConnection(): void
    {
        static::$connection = null;
    }


    // syntatic sugar for getting scoped query.
    public static function on(?string $connectionName = null): ModelQuery
    {
        return static::query($connectionName);
    }


    public static function getTableName(): string
    {
        if (!static::$tableName) {
            $modelName = self::class;
            throw new RuntimeException("Model '{$modelName}' does not define it's table name.");
        }

        return static::$tableName;
    }


    public static function query(?string $connectionName = null): ModelQuery
    {
        return new ModelQuery(static::getDatabase($connectionName), static::class);
    }


    public static function __callStatic($name, $arguments): mixed
    {
        return static::query()->$name(...$arguments);
    }
}
