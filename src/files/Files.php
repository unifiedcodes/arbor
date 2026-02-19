<?php

namespace Arbor\files;


use Arbor\files\contracts\FileRecordStoreInterface;
use Arbor\files\contracts\FileEntryInterface;
use Arbor\files\Keeper;
use Arbor\files\Filer;
use Arbor\files\FileRecord;


class Files
{
    private PolicyCatalog $policyCatalog;
    private Filer $filer;
    private Keeper $filesKeeper;


    public function __construct(
        private FileEntryInterface $fileEntry,
        private ?FileRecordStoreInterface $recordStore = null
    ) {
        $this->policyCatalog = new PolicyCatalog();

        $this->filer = new Filer($fileEntry, $this->policyCatalog);
        $this->filesKeeper = new Keeper($recordStore);
    }


    public function policies(array $policies)
    {
        $this->policyCatalog->registerPolicies($policies);
    }


    public function save(string $scheme, mixed $input, array $options = []): FileRecord
    {
        // consume options needed before policy resolution
        $fileRecord = $this->filer->save($scheme, $input, $options);

        $fileRecord = $this->filesKeeper->save($fileRecord);

        return $fileRecord;
    }
}
