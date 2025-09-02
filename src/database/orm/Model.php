<?php

namespace Arbor\database\orm;


use InvalidArgumentException;
use Arbor\database\orm\relations\HasOne;
use Arbor\database\orm\relations\HasMany;
use Arbor\database\orm\relations\BelongsTo;
use Arbor\database\orm\relations\BelongsToMany;
use Arbor\database\orm\relations\MorphOne;
use Arbor\database\orm\relations\MorphMany;
use Arbor\database\orm\relations\Relationship;


abstract class Model extends BaseModel
{
    protected array $relations = [];

    /**
     * Set the given relationship on the model.
     *
     * @param string $relation
     * @param mixed  $value
     * @return $this
     */
    public function setRelation(string $relation, $value): static
    {
        $this->relations[$relation] = $value;
        return $this;
    }

    /**
     * Get a specific relation if it is set.
     *
     * @param string $relation
     * @return mixed|null
     */
    public function getRelation(string $relation)
    {
        return $this->relations[$relation] ?? null;
    }

    /**
     * Get all of the current relations.
     *
     * @return array
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    // overriding Attribute trait's getAttribute method to check for relationships.

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
    // Save / delete
    // -----------------------

    public function save()
    {
        $query = static::query();
        $primaryKey = static::getPrimaryKey();

        if ($this->exists) {
            $dirty = $this->getDirty();

            if (empty($dirty)) {
                return true;
            }

            $affected = $query->where($primaryKey, $this->getAttribute($primaryKey))
                ->update($dirty);

            if ($affected > 0) {
                $this->syncOriginal();
                return true;
            }

            return false;
        }

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

        $query = static::query();
        $affected = $query->where($primaryKey, $primaryValue)->delete();

        if ($affected > 0) {
            $this->exists = false;
            $this->resetAttributes();
            return true;
        }

        return false;
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


    public function belongsToMany(
        Model $parent,
        string $related,
        string $pivotTable,
        string $foreignKey,     // FK in pivot referencing parent
        string $relatedKey,     // FK in pivot referencing related): Relationship
        array $pivotColumns = []     // Extra columns in pivot table.
    ) {
        return new BelongsToMany(
            $parent,
            $related,
            $pivotTable,
            $foreignKey,
            $relatedKey,
            $pivotColumns
        );
    }
}
