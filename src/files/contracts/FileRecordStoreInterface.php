<?php

namespace Arbor\files\contracts;

use Arbor\files\state\FileRecord;
use Arbor\storage\Uri;

/**
 * Defines the contract for a file record store responsible for persisting and retrieving file records.
 *
 * Implementations of this interface are responsible for providing the underlying
 * storage mechanism — such as a database, cache, or filesystem — used to manage
 * the lifecycle of {@see FileRecord} instances.
 *
 * @package Arbor\files\contracts
 */
interface FileRecordStoreInterface
{
    /**
     * Persist a new file record to the store.
     *
     * @param  FileRecord $record The file record to save.
     * @return FileRecord
     */
    public function save(FileRecord $record): FileRecord;

    /**
     * Update an existing file record in the store.
     *
     * @param  FileRecord $record The file record to update.
     * @return FileRecord         The updated file record reflecting the latest persisted state.
     */
    public function update(FileRecord $record): FileRecord;

    /**
     * Retrieve a file record by its unique identifier.
     *
     * @param  string|Uri $uri of the file.
     * @return FileRecord|null The matching file record, or null if not found.
     */
    public function find(string|Uri $uri): ?FileRecord;

    /**
     * Remove a file record from the store.
     *
     * @param  string|Uri $uri the file to delete.
     * @return void
     */
    public function delete(string|Uri $uri): void;


    public function exists(string|Uri $uri): bool;
}
