<?php

namespace Arbor\database\orm;


use ArrayAccess;
use JsonSerializable;
use RuntimeException;
use Arbor\database\Database;
use Arbor\database\DatabaseResolver;
use Arbor\database\orm\AttributesTrait;
use Arbor\database\orm\relations\BelongsTo;
use Arbor\database\orm\relations\Relationship;
use Arbor\database\orm\relations\HasOne;
use Arbor\database\orm\relations\HasMany;
use Arbor\database\orm\relations\MorphMany;
use Arbor\database\orm\relations\MorphOne;


abstract class Model implements ArrayAccess, JsonSerializable
{
    use AttributesTrait;


    private static ?DatabaseResolver $databaseResolver = null;
    protected static ?string $connection = null;
    protected static ?string $tableName = null;
    protected static string $primaryKey = 'id';
    protected bool $exists = false;
    protected array $relations = [];


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


    // Set default connection for each model, usually set by a provider.
    public static function setConnection($name)
    {
        static::$connection = $name;
    }


    public static function resetConnection(): void
    {
        static::$connection = null;
    }


    // Syntatic sugar for getting scoped query.
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


    public static function getPrimaryKey(): string
    {
        return static::$primaryKey;
    }


    public function getPrimaryKeyValue()
    {
        $primaryKey = $this->getPrimaryKey();   // e.g. "id"
        return $this->getAttribute($primaryKey);
    }


    public static function query(?string $connectionName = null): ModelQuery
    {
        return new ModelQuery(static::getDatabase($connectionName), static::class);
    }


    public static function __callStatic($name, $arguments): mixed
    {
        return static::query()->$name(...$arguments);
    }


    public function save()
    {
        $query = static::query();
        $primaryKey = static::getPrimaryKey();

        if ($this->exists) {
            // Get only dirty attributes
            $dirty = $this->getDirty();

            if (empty($dirty)) {
                return true; // Nothing to update
            }

            // Run update query
            $affected = $query->where($primaryKey, $this->getAttribute($primaryKey))
                ->update($dirty);

            if ($affected > 0) {
                // Merge updated fields into original state
                $this->syncOriginal();
                return true;
            }

            return false;
        }

        // Otherwise -> INSERT
        $id = $query->create($this->attributes)->getAttribute($primaryKey);

        $this->setAttribute($primaryKey, $id);
        $this->exists = true;
        $this->syncOriginal();
    }


    public function delete()
    {
        if (!$this->exists) {
            return false;
        }

        $primaryKey = static::getPrimaryKey();
        $primaryValue = $this->getAttribute($primaryKey);

        // Use ModelQuery to delete the row
        $query = new ModelQuery(static::getDatabase(), static::class);
        $affected = $query->where($primaryKey, $primaryValue)->delete();

        if ($affected > 0) {
            $this->exists = false;        // mark as deleted
            $this->resetAttributes();     // emptying the model to prevent misuse.
            return true;
        }

        return false;
    }


    // relationship methods.

    public function hasOne(string $related, string $foreignKey, ?string $localKey = null): Relationship
    {
        return new HasOne($this, $related, $foreignKey, $localKey);
    }


    public function hasMany(string $related, string $foreignKey, ?string $localKey = null): Relationship
    {
        return new HasMany($this, $related, $foreignKey, $localKey);
    }


    public function belongsTo(string $related, string $foreignKey, $ownerKey = null): Relationship
    {
        return new BelongsTo($this, $related, $foreignKey, $ownerKey);
    }


    public function morphOne(string $related, string $foreignKey, string $typeKey): Relationship
    {
        return new MorphOne($this, $related, $foreignKey, $typeKey);
    }


    public function morphMany(string $related, string $foreignKey, string $typeKey): Relationship
    {
        return new MorphMany($this, $related, $foreignKey, $typeKey);
    }
}
