<?php

namespace Arbor\files;


use Arbor\files\entries\FileEntryInterface;
use Arbor\files\FileContext;
use Arbor\files\policies\FilePolicyInterface;
use Arbor\files\PolicyCatalog;
use Arbor\files\Registry;
use RuntimeException;


final class Filer
{
    private PolicyCatalog $policyCatalog;

    public function __construct(
        private FileEntryInterface $entryPrototype,
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
        // return file record.
        return $this->register($fileContext, $policy);
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
        $registry = new Registry(
            $policy->store($fileContext),
            $policy->recordStore($fileContext)
        );

        return $registry->register($fileContext);
    }
}
