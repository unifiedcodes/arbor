<?php

namespace Arbor\files;


use Arbor\files\entries\FileEntryInterface;
use Arbor\files\strategies\FileStrategyInterface;
use Arbor\files\FileContext;
use Arbor\files\PolicyCatalog;
use LogicException;


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
        $policy = $this->policyCatalog->resolve($fileContext->get('mime'));


        // resolve strategy from policy.
        $strategy = $policy->strategy($fileContext);


        // prove the file.
        $fileContext = $this->proveFileType($strategy, $fileContext);


        print_r($fileContext);

        // filter the file.
        // normalize the file.
        // transform the file.
        // store the file.
        // return file record.
    }


    protected function proveFileType(FileStrategyInterface $strategy, FileContext $fileContext): FileContext
    {
        $fileContext = $strategy->prove($fileContext);

        if (!$fileContext->isProved()) {
            throw new LogicException($strategy::class . ' did not mark file as proved');
        }

        return $fileContext;
    }


    protected function normalizeFile(FileStrategyInterface $strategy, FileContext $fileContext)
    {
        $fileContext = $strategy->normalize($fileContext);

        if (!$fileContext->isNormalized()) {
            throw new LogicException($strategy::class . ' did not normalize file');
        }

        return $fileContext;
    }
}
