<?php

namespace Arbor\files;


use Arbor\files\contracts\FileEntryInterface;
use Arbor\files\contracts\FilePolicyInterface;
use Arbor\files\ingress\FileContext;
use Arbor\files\PolicyCatalog;
use Arbor\files\FileRecord;
use Arbor\files\Evaluator;
use Arbor\facades\Storage;
use Arbor\storage\Path;


final class Filer
{
    public function __construct(
        private FileEntryInterface $fileEntry,
        private PolicyCatalog $policyCatalog
    ) {}


    public function save(mixed $input, array $options = []): FileRecord
    {
        $fileEntry = $this->fileEntry->withInput($input);

        // create file context
        $fileContext = FileContext::fromPayload(
            $fileEntry->toPayload()
        );

        // resolve policy
        $policy = $this->resolvePolicy($fileContext, $options);

        // prove file
        $fileContext = $this->prove($fileContext, $policy);

        // evaluation and mutation.
        $fileContext = $this->evaluate($fileContext, $policy);

        // store the file.
        return $this->persist($fileContext, $policy);
    }


    protected function resolvePolicy(FileContext $fileContext, array $options = []): FilePolicyInterface
    {
        // infer options for namespace/mime/policySelector
        return $this->policyCatalog->resolve($fileContext->claimMime(), $options);
    }


    protected function prove(FileContext $fileContext, FilePolicyInterface $policy): FileContext
    {
        // get strategy from policy.
        $strategy = $policy->strategy($fileContext);

        // prove the file.
        return $strategy->prove($fileContext);
    }


    protected function evaluate(FileContext $fileContext, FilePolicyInterface $policy): FileContext
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


    protected function persist(FileContext $fileContext, FilePolicyInterface $policy): FileRecord
    {
        // policy->uri
        $uri = $policy->uri($fileContext);

        // context->filename
        $fileName = $fileContext->filename();

        // appending filename in uri
        $uri = $uri->withFileName($fileName);

        // uri->scheme
        $scheme = Storage::scheme($uri->scheme());

        // scheme->store
        $store = $scheme->store();

        // uri->absolutepath.
        $absolutePath = Path::absolutePath($scheme, $uri->path());

        // store->write
        $store->write(
            $absolutePath,
            $fileContext->stream()
        );

        // make filerecord.
        return FileRecord::from(
            context: $fileContext,
            uri: $uri->toString(),
        );
    }
}
