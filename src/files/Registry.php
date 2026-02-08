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


    public function register(): FileRecord {}
}
