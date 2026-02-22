<?php

namespace Arbor\files;

use Arbor\files\contracts\FileRecordStoreInterface;
use Arbor\files\utilities\NullFileRecordStore;
use Arbor\files\state\FileRecord;

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
        private FileRecordStoreInterface $recordStore = new NullFileRecordStore()
    ) {}


    /**
     * Persists the given FileRecord via the configured store and returns the result.
     *
     * The store may enrich or modify the record during persistence (e.g. assigning
     * a generated ID); the returned instance should always be used in place of the
     * original.
     *
     * @param FileRecord $fileRecord The record to persist.
     *
     * @return FileRecord The persisted record, which may differ from the input.
     */
    public function save(FileRecord $fileRecord): FileRecord
    {
        return $this->recordStore->save($fileRecord);
    }
}
