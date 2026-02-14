<?php

namespace Arbor\files;

use Arbor\files\record\FileRecord;
use Arbor\files\record\FileRecordStoreInterface;
use Arbor\files\record\NullFileRecordStore;

class FilesKeeper
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
