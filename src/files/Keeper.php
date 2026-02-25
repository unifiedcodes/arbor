<?php

namespace Arbor\files;

use Arbor\files\contracts\FileRecordStoreInterface;
use Arbor\files\state\FileRecord;
use Arbor\storage\Uri;

/**
 * Thin wrapper around a {@see FileRecordStoreInterface} that manages the
 * persistence of {@see FileRecord} instances after a successful file ingress.
 *
 * Keeper insulates the rest of the file pipeline from the presence or absence
 * of a concrete record store. When no store is injected, it silently falls back
 * to a {@see NullFileRecordStore}, ensuring that callers which do not require
 * record persistence can omit the dependency without any special-casing at the
 * call site.
 *
 * @package Arbor\files
 */
class Keeper
{
    /**
     * @param FileRecordStoreInterface|null $recordStore The store to persist records to;
     *                                                   falls back to {@see NullFileRecordStore} if null.
     */
    public function __construct(
        private FileRecordStoreInterface $recordStore
    ) {}


    /**
     * Persists the given FileRecord via the configured store and returns the result.
     *
     * The store may enrich or modify the record during persistence (e.g. assigning
     * a generated ID); the returned instance should always be used in place of the
     * original.
     *
     * @param FileRecord $FileRecord The record to persist.
     *
     * @return FileRecord The persisted record, which may differ from the input.
     */
    public function save(FileRecord $record): FileRecord
    {
        return $this->recordStore->save($record);
    }

    /**
     * Updates an existing FileRecord in the configured store and returns the result.
     *
     * The store may modify the record during the update (e.g. refreshing timestamps);
     * the returned instance should always be used in place of the original.
     *
     * @param FileRecord $record The record to update.
     *
     * @return FileRecord The updated record, which may differ from the input.
     */
    public function update(FileRecord $record): FileRecord
    {
        return $this->recordStore->update($record);
    }

    /**
     * Retrieves the FileRecord associated with the given URI, or null if not found.
     *
     * @param string|Uri $uri The URI of the file whose record should be retrieved.
     *
     * @return FileRecord|null The matching record, or null if no record exists for the given URI.
     */
    public function find(string|Uri $uri): ?FileRecord
    {
        return $this->recordStore->find($uri);
    }

    /**
     * Determines whether a FileRecord exists in the store for the given URI.
     *
     * @param string|Uri $uri The URI of the file to check.
     *
     * @return bool True if a record exists for the given URI, false otherwise.
     */
    public function exists(string|Uri $uri): bool
    {
        return $this->recordStore->exists($uri);
    }

    /**
     * Removes the FileRecord associated with the given URI from the store.
     *
     * @param string|Uri $uri The URI of the file whose record should be deleted.
     *
     * @return void
     */
    public function delete(string|Uri $uri): void
    {
        $this->recordStore->delete($uri);
    }
}
