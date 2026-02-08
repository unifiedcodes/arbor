<?php

namespace Arbor\files;


use Arbor\files\entries\FileEntryInterface;
use Arbor\files\FileContext;
use Arbor\files\PolicyCatalog;
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


    public function save(mixed $input)
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
        $this->filterFile($fileContext, $policy->filters($fileContext));


        // transform the file.
        // store the file.
        // return file record.
    }


    protected function filterFile(FileContext $fileContext, array $filters)
    {
        foreach ($filters as $filter) {

            // if filter fails.
            if (!$filter->filter($fileContext)) {
                throw new RuntimeException($filter->errorMessage($fileContext));
            }
        }
    }
}
