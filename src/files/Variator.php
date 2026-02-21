<?php

namespace Arbor\files;


use Arbor\files\contracts\VariantsPolicyInterface;
use Arbor\files\contracts\VariantProfileInterface;
use Arbor\files\Hydrator;
use Arbor\files\Evaluator;
use Arbor\files\PolicyCatalog;
use Arbor\facades\Storage;
use Arbor\files\state\FileContext;
use Arbor\files\state\VariantRecord;
use RuntimeException;
use Arbor\storage\Uri;


class Variator
{
    public function __construct(
        private PolicyCatalog $policyCatalog
    ) {}


    public function generate(string|Uri $uri, ?array $options = []): array
    {
        $uri = Storage::normalizeUri($uri);

        $fileContext = Hydrator::fromFileStat(Storage::stats($uri));

        $policy = $this->policyCatalog->resolvePolicy(
            VariantsPolicyInterface::class,
            $uri->scheme(),
            $fileContext->mime(),
            $options
        );


        if (!$policy instanceof VariantsPolicyInterface) {
            throw new RuntimeException("variant policy must implement VariantPolicyInterface");
        }


        $variants = $this->generateVariants(
            $policy,
            $fileContext
        );

        return $variants;
    }


    public function generateVariants(VariantsPolicyInterface $policy, FileContext $fileContext): array
    {
        $variants = [];

        foreach ($policy->variants($fileContext) as $profile) {
            $derivedContext = $this->createVariant($profile, $fileContext);

            $variants[] = $this->persistVariant($derivedContext, $policy, $profile);
        }

        return $variants;
    }


    public function createVariant(VariantProfileInterface $profile, FileContext $fileContext): FileContext
    {
        Evaluator::filters(
            $profile->filters($fileContext),
            $fileContext
        );

        $fileContext = Evaluator::transformers(
            $profile->transformers($fileContext),
            $fileContext
        );

        return $fileContext;
    }


    public function persistVariant(FileContext $fileContext, VariantsPolicyInterface $policy, VariantProfileInterface $profile): VariantRecord
    {
        // scheme
        $schemename = $policy->scheme();

        // policy->path + profile->path
        $path = joinPath($policy->path($fileContext), $profile->path());

        // adding suffix.
        $filename = $this->nameWithSuffix($fileContext, $profile->nameSuffix());

        // uri
        $uri = Storage::uriFromParts($schemename, $path, $filename);

        // ensuring stream
        $fileContext = Hydrator::ensureStream($fileContext);

        $stream = $fileContext->stream();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }



        // write
        Storage::write($uri, $stream);

        // record
        return new VariantRecord(
            name: $filename,
            uri: (string) $uri,
            mime: $fileContext->mime(),
            extension: $fileContext->extension(),
            size: $fileContext->size(),
        );
    }


    private function nameWithSuffix(FileContext $fileContext, string $suffix): string
    {
        $filename = $fileContext->name() . $suffix;

        $filename .= '.' . $fileContext->extension();

        return $filename;
    }
}
