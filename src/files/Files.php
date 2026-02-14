<?php

namespace Arbor\files;


use Arbor\files\record\FileRecordStoreInterface;
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
        private FileEntryInterface $fileEntry,
        private ?FileRecordStoreInterface $recordStore = null
    ) {
        $this->policyCatalog = new PolicyCatalog();

        $this->filer = new Filer($fileEntry, $this->policyCatalog);
        $this->filesKeeper = new FilesKeeper($recordStore);
    }


    public function policies(array $policies)
    {
        $this->policyCatalog->registerPolicies($policies);
    }


    public function save(mixed $input, array $options = []): FileRecord
    {
        // consume options needed before policy resolution
        $fileRecord = $this->filer->save($input, $options);

        $fileRecord = $this->filesKeeper->save($fileRecord);

        return $fileRecord;
    }
}
