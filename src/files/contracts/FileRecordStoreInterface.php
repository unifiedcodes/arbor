<?php

namespace Arbor\files\contracts;

use Arbor\files\state\FileRecord;

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
     * @return FileRecord         The saved file record, potentially with updated state (e.g., generated ID).
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
     * @param  string          $id The unique identifier of the file record.
     * @return FileRecord|null     The matching file record, or null if not found.
     */
    public function find(string $id): ?FileRecord;

    /**
     * Remove a file record from the store.
     *
     * @param  FileRecord $record The file record to delete.
     * @return void
     */
    public function delete(FileRecord $record): void;
}
