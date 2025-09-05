<?php

namespace Arbor\database\orm;

/**
 * Pivot Model Class
 * 
 * Represents a pivot/junction table in a many-to-many relationship between two models.
 * This class extends BaseModel and manages the intermediate table that connects
 * two related entities with additional pivot attributes.
 * 
 * @package Arbor\database\orm
 * @extends BaseModel
 */
class Pivot extends BaseModel
{
    /**
     * Extra pivot columns/attributes stored in the junction table
     * 
     * Contains additional data beyond the foreign keys that relate
     * the parent and related models (e.g., timestamps, status, etc.)
     * 
     * @var array<string, mixed>
     */
    protected array $pivotAttributes = [];

    /**
     * Name of the parent table in the relationship
     * 
     * @var string
     */
    protected string $parentTable = '';

    /**
     * Name of the related table in the relationship
     * 
     * @var string
     */
    protected string $relatedTable = '';

    /**
     * Name of the junction/pivot table that connects parent and related tables
     * 
     * @var string
     */
    protected string $junctionTable = '';

    /**
     * Foreign key column name that references the parent table
     * 
     * @var string
     */
    protected string $parentKey = '';

    /**
     * Foreign key column name that references the related table
     * 
     * @var string
     */
    protected string $relatedKey = '';

    /**
     * Constructor for Pivot model
     * 
     * Initializes a new pivot instance with the necessary table and key information
     * to manage a many-to-many relationship between two models.
     * 
     * @param string $junctionTable  Name of the pivot/junction table
     * @param string $parentTable    Name of the parent table
     * @param string $relatedTable   Name of the related table  
     * @param string $parentKey      Foreign key column referencing parent table
     * @param string $relatedKey     Foreign key column referencing related table
     * @param array<string, mixed> $pivotAttributes Additional pivot attributes/columns
     */
    public function __construct(
        string $junctionTable,
        string $parentTable,
        string $relatedTable,
        string $parentKey,
        string $relatedKey,
        array $pivotAttributes = []
    ) {
        parent::__construct();

        $this->junctionTable = $junctionTable;
        $this->parentTable = $parentTable;
        $this->relatedTable = $relatedTable;
        $this->parentKey = $parentKey;
        $this->relatedKey = $relatedKey;
        $this->pivotAttributes = $pivotAttributes;
    }
}
