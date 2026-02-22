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


/**
 * Orchestrates the generation of file variants for a stored file.
 *
 * Given a storage URI, Variator retrieves the file's metadata, resolves the
 * appropriate {@see VariantsPolicyInterface} from the catalog, and iterates over
 * the variant profiles declared by that policy. For each profile, it runs the
 * profile's filter and transformer pipelines against the source context, then
 * persists the derived file to storage and produces a {@see VariantRecord}.
 *
 * @package Arbor\files
 */
class Variator
{
    /**
     * @param PolicyCatalog $policyCatalog The catalog used to resolve the variants policy for a given scheme and MIME type.
     */
    public function __construct(
        private PolicyCatalog $policyCatalog
    ) {}


    /**
     * Generates all variants for the file at the given URI and returns their records.
     *
     * The URI is normalised, the file's metadata is read from storage, and the
     * appropriate {@see VariantsPolicyInterface} is resolved. All variant profiles
     * declared by the policy are then processed and persisted.
     *
     * @param string|Uri $uri     The URI of the source file for which variants should be generated.
     * @param array|null $options Optional overrides forwarded to the resolved variants policy.
     *
     * @return array<VariantRecord> An array of records describing each generated variant.
     *
     * @throws RuntimeException If the resolved policy does not implement {@see VariantsPolicyInterface}.
     */
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


    /**
     * Iterates over all variant profiles declared by the policy and produces a
     * VariantRecord for each one.
     *
     * For every profile returned by the policy, the source context is passed
     * through the profile's pipeline via {@see self::createVariant()}, and the
     * resulting derived context is persisted via {@see self::persistVariant()}.
     *
     * @param VariantsPolicyInterface $policy      The policy supplying the list of variant profiles.
     * @param FileContext             $fileContext  The proved source context to derive variants from.
     *
     * @return array<VariantRecord> An array of records describing each persisted variant.
     */
    public function generateVariants(VariantsPolicyInterface $policy, FileContext $fileContext): array
    {
        $variants = [];

        foreach ($policy->variants($fileContext) as $profile) {
            $derivedContext = $this->createVariant($profile, $fileContext);

            $variants[] = $this->persistVariant($derivedContext, $policy, $profile);
        }

        return $variants;
    }


    /**
     * Applies a variant profile's filter and transformer pipelines to the source
     * context and returns the derived FileContext.
     *
     * Filters are run first in a fail-fast manner; transformers are then applied
     * as a reducer. The returned context represents the file as it should appear
     * for this specific variant.
     *
     * @param VariantProfileInterface $profile     The profile defining the filters and transformers for this variant.
     * @param FileContext             $fileContext  The source context to derive from.
     *
     * @return FileContext The derived context after all profile filters and transformers have been applied.
     */
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


    /**
     * Persists a derived FileContext to storage as a variant and returns its record.
     *
     * The storage URI is assembled from the policy's scheme, the joined policy and
     * profile paths, and a suffixed filename. The context stream is ensured before
     * writing and rewound if seekable. The resulting {@see VariantRecord} captures
     * the variant's URI, metadata, and the profile's name suffix as its type.
     *
     * @param FileContext             $fileContext The derived context to persist.
     * @param VariantsPolicyInterface $policy      The policy providing the scheme and base path.
     * @param VariantProfileInterface $profile     The profile providing the sub-path and name suffix.
     *
     * @return VariantRecord A record describing the persisted variant file.
     */
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
            type: $profile->nameSuffix()
        );
    }


    /**
     * Builds a filename by appending an underscore-separated suffix to the context's
     * base name, followed by its extension.
     *
     * For example, a context with name "photo" and extension "jpg" combined with
     * suffix "thumb" produces "photo_thumb.jpg".
     *
     * @param FileContext $fileContext The proved context supplying the base name and extension.
     * @param string      $suffix      The suffix to append to the base name.
     *
     * @return string The suffixed filename including extension.
     */
    private function nameWithSuffix(FileContext $fileContext, string $suffix): string
    {
        $filename = $fileContext->name() . '_' . $suffix;

        $filename .= '.' . $fileContext->extension();

        return $filename;
    }
}
