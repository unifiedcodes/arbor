<?php

namespace Arbor\files;


use Arbor\files\contracts\FileEntryInterface;
use Arbor\files\contracts\FilePolicyInterface;
use Arbor\files\ingress\FileContext;
use Arbor\files\PolicyCatalog;
use Arbor\files\FileRecord;
use Arbor\facades\Storage;
use Arbor\storage\Path;
use RuntimeException;


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
        $policy = $this->policyCatalog->resolve($fileContext->claimMime(), $options);

        // resolve strategy from policy.
        $strategy = $policy->strategy($fileContext);

        // prove the file.
        $fileContext = $strategy->prove($fileContext);

        // filter the file.
        $this->filterFile($fileContext, $policy);

        // transform the file.
        $fileContext = $this->transformFile($fileContext, $policy);


        // store the file.
        return $this->persist($fileContext, $policy);

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
            publicUrl: Path::publicUrl($scheme, $uri->path()),
        );
    }
}
