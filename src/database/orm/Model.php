<?php

namespace Arbor\database\orm;


use InvalidArgumentException;
use Arbor\database\orm\relations\HasOne;
use Arbor\database\orm\relations\HasMany;
use Arbor\database\orm\relations\BelongsTo;
use Arbor\database\orm\relations\MorphOne;
use Arbor\database\orm\relations\MorphMany;
use Arbor\database\orm\relations\Relationship;


abstract class Model extends BaseModel
{
    protected array $relations = [];


    public function getAttribute(string $key, mixed $default = null): mixed
    {
        // Direct attribute exists
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        // Check if a method exists for this key (relationship)
        if (method_exists($this, $key)) {

            // Lazy load relationship
            if (!isset($this->relations[$key])) {
                $relation = $this->$key();

                // Auto-resolve if it is a Relationship
                if ($relation instanceof Relationship) {
                    $relation = $relation->resolve();
                }

                $this->relations[$key] = $relation;
            }

            return $this->relations[$key];
        }

        // Fallback to default value if provided
        if (func_num_args() === 2) {
            return $default;
        }

        throw new InvalidArgumentException(
            "The attribute or relation '{$key}' is not defined for the model '" . static::class . "'."
        );
    }

    // -----------------------
    // Relationship methods
    // -----------------------

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
