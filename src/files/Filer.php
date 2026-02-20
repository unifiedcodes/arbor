<?php

namespace Arbor\files;


use Arbor\files\contracts\FileEntryInterface;
use Arbor\files\contracts\IngressPolicyInterface;
use Arbor\files\ingress\FileContext;
use Arbor\files\PolicyCatalog;
use Arbor\files\FileRecord;
use Arbor\files\Evaluator;
use Arbor\facades\Storage;


final class Filer
{
    public function __construct(
        private FileEntryInterface $fileEntry,
        private PolicyCatalog $policyCatalog
    ) {}


    public function save(string $scheme, mixed $input, array $options = []): FileRecord
    {
        $fileEntry = $this->fileEntry->withInput($input);

        // create file context
        $fileContext = FileContext::fromPayload(
            $fileEntry->toPayload()
        );

        // resolve policy
        $policy = $this->resolvePolicy($scheme, $fileContext, $options);

        // prove file
        $fileContext = $this->prove($fileContext, $policy);

        // evaluation and mutation.
        $fileContext = $this->evaluate($fileContext, $policy);

        // store the file.
        return $this->persist($fileContext, $policy);
    }


    protected function resolvePolicy(string $scheme, FileContext $fileContext, array $options = []): IngressPolicyInterface
    {
        return $this->policyCatalog->resolvePolicy(
            IngressPolicyInterface::class,
            $scheme,
            $fileContext->claimMime(),
            $options
        );
    }


    protected function prove(FileContext $fileContext, IngressPolicyInterface $policy): FileContext
    {
        // get strategy from policy.
        $strategy = $policy->strategy($fileContext);

        // prove the file.
        return $strategy->prove($fileContext);
    }


    protected function evaluate(FileContext $fileContext, IngressPolicyInterface $policy): FileContext
    {
        // filter the file.
        Evaluator::filters(
            $policy->filters($fileContext),
            $fileContext
        );

        // transform the file.
        $fileContext = Evaluator::transformers(
            $policy->transformers($fileContext),
            $fileContext
        );

        return $fileContext;
    }


    protected function persist(FileContext $fileContext, IngressPolicyInterface $policy): FileRecord
    {
        // policy->scheme name
        $schemename = $policy->scheme();

        // policy->path
        $path = $policy->path($fileContext);

        // context->filename
        $filename = $fileContext->filename();

        // uri
        $uri = Storage::uriFromParts($schemename, $path, $filename);

        // uri->store
        $store = Storage::store($uri);

        // store->write
        $store->write(
            Storage::absolutePath($uri),
            $fileContext->stream()
        );

        // make filerecord.
        return FileRecord::from(
            context: $fileContext,
            uri: $uri->toString(),
        );
    }
}
