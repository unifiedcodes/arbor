<?php

namespace Arbor\files\contracts;


use Arbor\files\state\FileRecord;


interface FileRecordStoreInterface
{
    public function save(FileRecord $record): FileRecord;

    public function update(FileRecord $record): FileRecord;

    public function find(string $id): ?FileRecord;

    public function delete(FileRecord $record): void;
}
