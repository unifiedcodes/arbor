<?php

namespace Arbor\files\utilities;

use Arbor\files\state\FileRecord;
use Arbor\files\contracts\FileRecordStoreInterface;
use Arbor\storage\Uri;


/**
 * No-op implementation of {@see FileRecordStoreInterface} that discards all
 * persistence operations.
 *
 * Useful as a default store when record persistence is not required, as a
 * stand-in during testing, or as a safe null object in contexts where a store
 * must be injected but storage behaviour is intentionally suppressed.
 *
 * All write operations return the record unchanged and all read operations
 * return null, faithfully implementing the null object pattern.
 *
 * @package Arbor\files\utilities
 */
final class NullFileRecordStore implements FileRecordStoreInterface
{
    /**
     * No-op save. Returns the record untouched without persisting it.
     *
     * @param FileRecord $record The record to save.
     *
     * @return FileRecord The same record, unmodified.
     */
    public function save(FileRecord $record): FileRecord
    {
        // No persistence
        return $record;
    }

    /**
     * No-op update. Returns the record untouched without persisting any changes.
     *
     * @param FileRecord $record The record to update.
     *
     * @return FileRecord The same record, unmodified.
     */
    public function update(FileRecord $record): FileRecord
    {
        // No-op update
        return $record;
    }

    /**
     * Always returns null, as nothing is ever stored by this implementation.
     *
     * @param string|Uri identifier to look up.
     *
     * @return null Always null.
     */
    public function find(string|Uri $uri): ?FileRecord
    {
        // Nothing is ever stored
        return null;
    }

    /**
     * No-op delete. Takes no action and returns nothing.
     *
     * @param FileRecord $record The record to delete.
     *
     * @return void
     */
    public function delete(string|Uri $uri): void
    {
        // No-op delete
    }


    public function exists(string|Uri $uri): bool
    {
        return true;
    }
}
