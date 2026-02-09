<?php

namespace Arbor\files;


use Arbor\files\recordStores\FileRecordStoreInterface;
use Arbor\files\stores\FileStoreInterface;
use Arbor\files\FileRecord;


final class Registry
{
    public function __construct(
        private FileStoreInterface $store,
        private ?FileRecordStoreInterface $recordStore
    ) {}


    public function register(FileContext $fileContext, string $path, ?string $namespace = ''): FileRecord
    {
        $this->store->write($fileContext, $path);

        $uri = $this->store->key() . $path . $fileContext->name();

        $publicURL = $this->store->rootURL . $path . $fileContext->name();

        $record = FileRecord::from(
            context: $fileContext,
            storeKey: $this->store->key(),
            path: $path,
            uri: $uri,
            publicURL: $publicURL,
            namespace: $namespace
        );

        // $this->recordStore->save($record);

        return $record;
    }
}
