<?php

namespace Arbor\files\record;

use Arbor\files\record\FileRecord;


final class NullFileRecordStore implements FileRecordStoreInterface
{
    public function save(FileRecord $record): FileRecord
    {
        // No persistence, just return the record untouched
        return $record;
    }

    public function update(FileRecord $record): FileRecord
    {
        // No-op update
        return $record;
    }

    public function find(string $id): ?FileRecord
    {
        // Nothing is ever stored
        return null;
    }

    public function delete(FileRecord $record): void
    {
        // No-op delete
    }
}
