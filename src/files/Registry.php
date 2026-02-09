<?php

namespace Arbor\files;


use Arbor\files\recordStores\FileRecordStoreInterface;
use Arbor\files\stores\FileStoreInterface;
use Arbor\files\FileRecord;
use LogicException;


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


    public static function generateURI(
        string $store,
        string $namespace,
        string $id,
        string $extension,
        ?string $variant = null
    ): string {

        if ($store === '' || $namespace === '' || $id === '' || $extension === '') {
            throw new LogicException('Invalid arguments for URI generation');
        }

        $variantPart = $variant !== null ? "~{$variant}" : '';

        return sprintf(
            '%s://%s/%s%s.%s',
            $store,
            $namespace,
            $id,
            $variantPart,
            $extension
        );
    }
}
