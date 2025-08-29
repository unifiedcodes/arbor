<?php

namespace Arbor\database\orm;

use Exception;
use ArrayAccess;
use JsonSerializable;
use Arbor\database\Database;
use Arbor\database\orm\ModelQuery;
use Arbor\database\orm\AttributesTrait;


abstract class Model implements ArrayAccess, JsonSerializable
{
    use AttributesTrait;

    protected static ?string $tableName = null;
    protected static Database $database;
    protected string $primaryKey = 'id';
    protected bool $exists = false;


    public function __construct(array $attributes = [], bool $exists = false)
    {
        if (!static::$tableName) {
            $classname = get_class($this);
            throw new Exception("table name is not defined by Model Class: '{$classname}'");
        }

        if (!static::$database) {
            throw new Exception("Database Instance is not bound with Model Class");
        }

        $this->fill($attributes);
        $this->syncOriginal();
        $this->exists = $exists;
    }


    public static function setDatabase(Database $db): void
    {
        static::$database = $db;
    }


    public static function getDatabase(): Database
    {
        return static::$database;
    }


    public static function tableName(): string
    {
        return static::$tableName;
    }


    public static function query(): ModelQuery
    {
        return new ModelQuery(static::getDatabase(), static::class);
    }


    public function all(): array
    {
        return static::query()->get();
    }
}
