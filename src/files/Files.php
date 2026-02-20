<?php

namespace Arbor\files;


use Arbor\files\contracts\FilePolicyInterface;
use Arbor\files\contracts\FileRecordStoreInterface;
use Arbor\files\contracts\FileEntryInterface;
use Arbor\files\Keeper;
use Arbor\files\Filer;
use Arbor\files\FileRecord;
use Arbor\storage\Uri;


class Files
{
    private PolicyCatalog $policyCatalog;
    private Filer $filer;
    private Keeper $filesKeeper;
    private Variator $variator;


    public function __construct(
        private FileEntryInterface $fileEntry,
        private ?FileRecordStoreInterface $recordStore = null
    ) {
        $this->policyCatalog = new PolicyCatalog();

        $this->filer = new Filer($fileEntry, $this->policyCatalog);
        $this->filesKeeper = new Keeper($recordStore);
        $this->variator = new Variator($this->policyCatalog);
    }


    public function registerPolicies(array $policies)
    {
        $this->policyCatalog->registerPolicies($policies);
    }


    public function resolvePolicy(string $interfaceFqn, string $scheme, string $mime, array $options = []): FilePolicyInterface
    {
        return $this->policyCatalog->resolvePolicy(
            $interfaceFqn,
            $scheme,
            $mime,
            $options
        );
    }


    public function save(string $scheme, mixed $input, array $options = []): FileRecord
    {
        // consume options needed before policy resolution
        $fileRecord = $this->filer->save($scheme, $input, $options);

        $fileRecord = $this->filesKeeper->save($fileRecord);

        return $fileRecord;
    }


    public function createVariations(string|Uri $uri)
    {
        $variations = $this->variator->generate($uri);

        print_r($variations);
    }
}
