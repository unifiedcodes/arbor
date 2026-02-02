<?php

namespace Arbor\database\orm;

use InvalidArgumentException;
use Arbor\database\orm\relations\HasOne;
use Arbor\database\orm\relations\HasMany;
use Arbor\database\orm\relations\BelongsTo;
use Arbor\database\orm\relations\BelongsToMany;
use Arbor\database\orm\relations\MorphOne;
use Arbor\database\orm\relations\MorphMany;
use Arbor\database\orm\relations\MorphToMany;
use Arbor\database\orm\relations\Relationship;

/**
 * Abstract Model class for ORM functionality.
 * 
 * This class extends BaseModel and provides relationship management,
 * attribute handling, and basic CRUD operations for database models.
 * It supports various types of relationships including HasOne, HasMany,
 * BelongsTo, BelongsToMany, MorphOne, and MorphMany.
 *
 * @package Arbor\database\orm
 */
abstract class Model extends BaseModel
{
    /**
     * Array to store loaded relationships for the model instance.
     *
     * @var array<string, mixed>
     */
    protected array $relations = [];

    /**
     * Attributes that are allowed for mass assignment.
     *
     * @var array<int, string>
     */
    protected array $fillable = [];

    /**
     * Set the given relationship on the model.
     *
     * @param string $relation The name of the relationship
     * @param mixed  $value    The relationship data/model(s)
     * @return static Returns the current model instance for method chaining
     */
    public function setRelation(string $relation, $value): static
    {
        $this->relations[$relation] = $value;
        return $this;
    }

    /**
     * Check if a specific relationship is loaded on the model.
     *
     * @param string $key The relationship name to check
     * @return bool True if the relationship exists, false otherwise
     */
    public function hasRelation(string $key)
    {
        return isset($this->relations[$key]);
    }

    /**
     * Get a specific relation if it is set.
     *
     * @param string $relation The name of the relationship to retrieve
     * @return mixed|null The relationship data or null if not found
     */
    public function getRelation(string $relation)
    {
        return $this->relations[$relation] ?? null;
    }

    /**
     * Get all of the current relations.
     *
     * @return array<string, mixed> Array of all loaded relationships
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * Get an attribute value from the model.
     * 
     * This method overrides the Attribute trait's getAttribute method to check for relationships.
     * It follows this priority order:
     * 1. Direct attributes from the model
     * 2. Already loaded relationships
     * 3. Relationship methods (lazy loading)
     * 4. Default value if provided
     * 5. Throws exception if attribute/relation not found
     *
     * @param string $key     The attribute or relationship name
     * @param mixed  $default Optional default value if attribute is not found
     * @return mixed The attribute value, relationship data, or default value
     * @throws InvalidArgumentException When the attribute or relation is not defined
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        // Direct attribute exists
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        // if relationship exists.
        if ($this->hasRelation($key)) {
            return $this->getRelation($key);
        }

        // Check if a method exists for this key (relationship)
        if (method_exists($this, $key)) {
            $called = $this->$key();

            // cache and return if it is Relationship.
            if ($called instanceof Relationship) {

                $resolved = $called->resolve();

                $this->setRelation($key, $resolved);

                return $resolved;
            }

            return $called;
        }

        // Fallback to default value if provided
        if (func_num_args() === 2) {
            return $default;
        }

        throw new InvalidArgumentException(
            "The attribute or relation '{$key}' is not defined for the model '" . static::class . "'."
        );
    }


    public function getFillable(): array
    {
        return $this->fillable;
    }


    public function isFillable(string $key): bool
    {
        // If fillable is empty, allow nothing
        if (empty($this->fillable)) {
            return false;
        }

        return in_array($key, $this->fillable, true);
    }
    // -----------------------
    // Save / delete
    // -----------------------

    /**
     * Save the model to the database.
     * 
     * This method handles both creating new records and updating existing ones.
     * For existing models, it only updates dirty (changed) attributes.
     * For new models, it creates a new record and sets the model as existing.
     *
     * @return bool True if the save operation was successful, false otherwise
     */
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

    /**
     * Delete the model from the database.
     * 
     * This method removes the model record from the database using the primary key.
     * After successful deletion, it marks the model as non-existing and resets attributes.
     *
     * @return bool True if the delete operation was successful, false otherwise
     */
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

    /**
     * Define a one-to-one relationship.
     * 
     * This relationship indicates that the current model has one instance
     * of the related model.
     *
     * @param string      $related    The fully qualified class name of the related model
     * @param string      $foreignKey The foreign key on the related table
     * @param string|null $localKey   The local key on this model (defaults to primary key)
     * @return Relationship HasOne relationship instance
     */
    public function hasOne(string $related, string $foreignKey, ?string $localKey = null): Relationship
    {
        return new HasOne($this, $related, $foreignKey, $localKey);
    }

    /**
     * Define a one-to-many relationship.
     * 
     * This relationship indicates that the current model has many instances
     * of the related model.
     *
     * @param string      $related    The fully qualified class name of the related model
     * @param string      $foreignKey The foreign key on the related table
     * @param string|null $localKey   The local key on this model (defaults to primary key)
     * @return Relationship HasMany relationship instance
     */
    public function hasMany(string $related, string $foreignKey, ?string $localKey = null): Relationship
    {
        return new HasMany($this, $related, $foreignKey, $localKey);
    }

    /**
     * Define an inverse one-to-one or one-to-many relationship.
     * 
     * This relationship indicates that the current model belongs to
     * an instance of the related model.
     *
     * @param string      $related   The fully qualified class name of the related model
     * @param string      $foreignKey The foreign key on this model's table
     * @param string|null $ownerKey   The primary key on the related model's table
     * @return Relationship BelongsTo relationship instance
     */
    public function belongsTo(string $related, string $foreignKey, $ownerKey = null): Relationship
    {
        return new BelongsTo($this, $related, $foreignKey, $ownerKey);
    }

    /**
     * Define a polymorphic one-to-one relationship.
     * 
     * This relationship allows the current model to belong to multiple
     * other model types through a single association.
     *
     * @param string $related    The fully qualified class name of the related model
     * @param string $foreignKey The foreign key column name
     * @param string $typeKey    The column name that stores the model type
     * @return Relationship MorphOne relationship instance
     */
    public function morphOne(string $related, string $foreignKey, string $typeKey): Relationship
    {
        return new MorphOne($this, $related, $foreignKey, $typeKey);
    }

    /**
     * Define a polymorphic one-to-many relationship.
     * 
     * This relationship allows the current model to have many instances
     * of the related model through a polymorphic association.
     *
     * @param string $related    The fully qualified class name of the related model
     * @param string $foreignKey The foreign key column name
     * @param string $typeKey    The column name that stores the model type
     * @return Relationship MorphMany relationship instance
     */
    public function morphMany(string $related, string $foreignKey, string $typeKey): Relationship
    {
        return new MorphMany($this, $related, $foreignKey, $typeKey);
    }


    /**
     * Define a polymorphic many-to-many relationship.
     *
     * Example: Post <-> Tag through taggables
     *
     * @param string $related        Related model class
     * @param string $pivotTable     Pivot table name
     * @param string $foreignKey     FK in pivot referencing parent
     * @param string $relatedKey     FK in pivot referencing related
     * @param string $morphType      Column storing morph type
     * @param string $morphClass     Morph class value (usually static::class)
     * @param array  $pivotColumns  Extra pivot columns
     * @return Relationship
     */
    public function morphToMany(
        string $related,
        string $pivotTable,
        string $foreignKey,
        string $relatedKey,
        string $morphType,
        string $morphClass,
        array $pivotColumns = []
    ): Relationship {
        return new MorphToMany(
            $this,
            $related,
            $pivotTable,
            $foreignKey,
            $relatedKey,
            $morphType,
            $morphClass,
            $pivotColumns
        );
    }

    /**
     * Define a many-to-many relationship.
     * 
     * This relationship connects two models through an intermediate pivot table.
     * It allows many instances of the current model to be associated with
     * many instances of the related model.
     *
     * @param string $related       The fully qualified class name of the related model
     * @param string $pivotTable    The name of the intermediate/pivot table
     * @param string $foreignKey    The foreign key in pivot table referencing parent model
     * @param string $relatedKey    The foreign key in pivot table referencing related model
     * @param array  $pivotColumns  Additional columns to retrieve from the pivot table
     * @return Relationship BelongsToMany relationship instance
     */
    public function belongsToMany(
        string $related,
        string $pivotTable,
        string $foreignKey,     // FK in pivot referencing parent
        string $relatedKey,     // FK in pivot referencing related): Relationship
        array $pivotColumns = []     // Extra columns in pivot table.
    ): Relationship {
        return new BelongsToMany(
            $this,
            $related,
            $pivotTable,
            $foreignKey,
            $relatedKey,
            $pivotColumns
        );
    }
}
