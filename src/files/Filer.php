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


    public function upload(mixed $input)
    {
        $fileEntry = $this->entryPrototype->withInput($input);

        // create file context
        $fileContext = FileContext::fromPayload(
            $fileEntry->toPayload()
        );
    }


    protected function useStrategy(
        FileStrategyInterface $strategy,
        FileContext $context
    ): FileContext {
        $context = $strategy->prove($context);

        if (!$context->isProved()) {
            throw new LogicException(
                sprintf('%s did not mark file as proved', $strategy::class)
            );
        }

        $context = $strategy->normalize($context);

        if (!$context->isNormalized()) {
            throw new LogicException(
                sprintf('%s did not normalize file', $strategy::class)
            );
        }

        return $context;
    }
}
