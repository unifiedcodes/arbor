<?php

namespace Arbor\files\record;


use Arbor\files\record\FileRecord;


interface FileRecordStoreInterface
{
    public function save(FileRecord $record): FileRecord;

    public function update(FileRecord $record): FileRecord;

    public function find(string $id): ?FileRecord;

    public function delete(FileRecord $record): void;
}
