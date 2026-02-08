<?php

namespace Arbor\files\recordStores;


use Arbor\files\FileRecord;


interface FileRecordStoreInterface
{
    public function save(FileRecord $record): void;

    public function update(FileRecord $record): void;

    public function delete(FileRecord $record): void;

    public function find(string $id): ?FileRecord;
}
