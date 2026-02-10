<?php

namespace Arbor\files;


use Arbor\files\recordStores\FileRecordStoreInterface;
use Arbor\files\entries\FileEntryInterface;
use Arbor\files\FilesKeeper;
use Arbor\files\Filer;
use Arbor\files\record\FileRecord;


class Files
{
    private PolicyCatalog $policyCatalog;
    private Filer $filer;
    private FilesKeeper $filesKeeper;


    public function __construct(
        private FileEntryInterface $entryPrototype,
        private ?FileRecordStoreInterface $recordStore
    ) {
        $this->policyCatalog = new PolicyCatalog();

        $this->filer = new Filer($entryPrototype, $this->policyCatalog);
        $this->filesKeeper = new FilesKeeper();
    }


    public function policies(array $policies)
    {
        $this->policyCatalog->registerPolicies($policies);
    }


    public function save(mixed $input, array $options = []): FileRecord
    {
        // consume options needed before policy resolution
        return $this->filer->save($input, $options);
    }
}
