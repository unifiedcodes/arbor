<?php

namespace Arbor\files;

use Arbor\files\contracts\FileRecordStoreInterface;
use Arbor\files\utilities\NullFileRecordStore;
use Arbor\files\state\FileRecord;

class Keeper
{
    public function __construct(
        private ?FileRecordStoreInterface $recordStore
    ) {
        if (!$recordStore) {
            $this->recordStore = new NullFileRecordStore();
        }
    }


    public function save(FileRecord $fileRecord): FileRecord
    {
        return $this->recordStore->save($fileRecord);
    }
}
