<?php

namespace Arbor\files;


use Arbor\files\entries\FileEntryInterface;
use Arbor\files\ingress\FileContext;
use Arbor\files\policies\FilePolicyInterface;
use Arbor\files\PolicyCatalog;
use Arbor\files\recordStores\FileRecordStoreInterface;
use Arbor\files\record\FileRecord;
use RuntimeException;


final class Filer
{
    private PolicyCatalog $policyCatalog;

    public function __construct(
        private FileEntryInterface $entryPrototype,
        private ?FileRecordStoreInterface $recordStore
    ) {
        $this->policyCatalog = new PolicyCatalog();
    }


    public function policies(array $policies)
    {
        $this->policyCatalog->registerPolicies($policies);
    }


    public function save(mixed $input): FileRecord
    {
        $fileEntry = $this->entryPrototype->withInput($input);

        // create file context
        $fileContext = FileContext::fromPayload(
            $fileEntry->toPayload()
        );

        // resolve policy
        $policy = $this->policyCatalog->resolve($fileContext->claimMime());

        // resolve strategy from policy.
        $strategy = $policy->strategy($fileContext);

        // prove the file.
        $fileContext = $strategy->prove($fileContext);

        // filter the file.
        $this->filterFile($fileContext, $policy);

        // transform the file.
        $fileContext = $this->transformFile($fileContext, $policy);


        // store the file.
        return $this->register($fileContext, $policy);

        // generate variants.

        // return enriched with variants file record.
    }


    protected function filterFile(FileContext $fileContext, FilePolicyInterface $policy): void
    {
        $filters = $policy->filters($fileContext);

        foreach ($filters as $filter) {

            // if filter fails.
            if (!$filter->filter($fileContext)) {
                throw new RuntimeException($filter->errorMessage($fileContext));
            }
        }
    }


    protected function transformFile(FileContext $fileContext, FilePolicyInterface $policy): FileContext
    {
        $transformers = $policy->transformers($fileContext);

        foreach ($transformers as $transformer) {
            $fileContext = $transformer->transform($fileContext);
        }

        return $fileContext;
    }


    protected function register(FileContext $fileContext, FilePolicyInterface $policy): FileRecord
    {
        $store = $policy->store($fileContext);
        $path = $policy->storePath($fileContext);

        // write in safe place.
        // mutate filecontext.
        // make a fileRecord from FileRecord Factory.
        // optionally keep in recordstore.
        // return filerecord.
    }
}
