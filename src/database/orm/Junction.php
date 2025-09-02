<?php

namespace Arbor\database\orm;



class Junction extends BaseModel
{
    // Extra pivot columns
    protected array $pivotAttributes = [];

    protected string $parentTable = '';
    protected string $relatedTable = '';
    protected string $junctionTable = '';

    protected string $parentKey = '';
    protected string $relatedKey = '';


    public function __construct(
        string $junctionTable,
        // string $parentTable,
        // string $relatedTable,
        // string $parentKey,
        // string $relatedKey,
        // array $pivotAttributes = []
    ) {
        parent::__construct();

        // $this->junctionTable = $junctionTable;
        // $this->parentTable = $parentTable;
        // $this->relatedTable = $relatedTable;
        // $this->parentKey = $parentKey;
        // $this->relatedKey = $relatedKey;
    }
}
