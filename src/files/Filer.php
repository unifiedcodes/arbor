<?php

namespace Arbor\files;


use Arbor\files\contracts\FileEntryInterface;
use Arbor\files\contracts\IngressPolicyInterface;
use Arbor\files\state\FileContext;
use Arbor\files\PolicyCatalog;
use Arbor\files\state\FileRecord;
use Arbor\files\Evaluator;
use Arbor\facades\Storage;


/**
 * Orchestrates the full ingress lifecycle for a file: payload extraction,
 * context hydration, policy resolution, proving, evaluation, and persistence.
 *
 * The Filer acts as the primary entry point for saving files. It delegates each
 * discrete concern to a focused collaborator — {@see FileEntryInterface} for input
 * normalisation, {@see Hydrator} for context construction, {@see PolicyCatalog}
 * for policy resolution, and {@see Evaluator} for filter/transformer pipelines —
 * coordinating them into a single linear flow via {@see self::save()}.
 *
 * @package Arbor\files
 */
final class Filer
{
    /**
     * @param FileEntryInterface $fileEntry    Normalises raw input into a transferable payload.
     * @param PolicyCatalog      $policyCatalog Resolves the appropriate ingress policy for a given scheme and MIME type.
     */
    public function __construct(
        private FileEntryInterface $fileEntry,
        private PolicyCatalog $policyCatalog
    ) {}


    /**
     * Saves a file through the full ingress pipeline and returns a populated FileRecord.
     *
     * The pipeline proceeds as follows:
     *  1. The raw input is normalised into a {@see Payload} via the file entry.
     *  2. A {@see FileContext} is hydrated from the payload.
     *  3. The appropriate {@see IngressPolicyInterface} is resolved from the catalog.
     *  4. The context is proved (metadata verified) using the policy's strategy.
     *  5. The proved context is filtered and transformed according to the policy.
     *  6. The context is persisted to storage and a {@see FileRecord} is returned.
     *
     * @param string $scheme  The storage scheme identifier used to resolve the policy and destination (e.g. "avatars", "documents").
     * @param mixed  $input   The raw file input accepted by the configured {@see FileEntryInterface}.
     * @param array  $options Optional overrides forwarded to the resolved policy.
     *
     * @return FileRecord A fully populated record describing the persisted file.
     */
    public function save(string $scheme, mixed $input, array $options = []): FileRecord
    {
        $fileEntry = $this->fileEntry->withInput($input);

        // create file context
        $fileContext = Hydrator::fromPayload($fileEntry->toPayload());

        // resolve policy
        $policy = $this->policyCatalog->resolvePolicy(
            IngressPolicyInterface::class,
            $scheme,
            $fileContext->inspectMime(),
            $options
        );

        // prove file
        $fileContext = $this->prove($fileContext, $policy);

        // evaluation and mutation.
        $fileContext = $this->evaluate($fileContext, $policy);

        // store the file.
        return $this->persist($fileContext, $policy);
    }


    /**
     * Proves the FileContext using the strategy provided by the given policy.
     *
     * Proving resolves and verifies all core metadata (MIME type, extension,
     * size, binary flag), transitioning the context into the proved state
     * required by downstream operations.
     *
     * @param FileContext           $fileContext The unproved context to prove.
     * @param IngressPolicyInterface $policy      The policy whose strategy performs the proving.
     *
     * @return FileContext A proved FileContext with verified metadata.
     */
    protected function prove(FileContext $fileContext, IngressPolicyInterface $policy): FileContext
    {
        // get strategy from policy.
        $strategy = $policy->strategy($fileContext);

        // prove the file.
        return $strategy->prove($fileContext);
    }


    /**
     * Runs the policy's filter and transformer pipelines against the FileContext.
     *
     * Filters are applied first in a fail-fast manner; any violation will halt
     * execution immediately. Transformers are then applied as a reducer, each
     * receiving the context returned by the previous one.
     *
     * @param FileContext           $fileContext The proved context to evaluate.
     * @param IngressPolicyInterface $policy      The policy supplying the filter and transformer lists.
     *
     * @return FileContext The context after all transformers have been applied.
     */
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


    /**
     * Persists the file to storage and produces a FileRecord from the result.
     *
     * The storage URI is constructed from the policy's scheme name, the
     * policy-derived path, and the context's filename. The context stream is
     * ensured before writing, and the resulting record captures the final URI
     * alongside all verified metadata.
     *
     * @param FileContext           $fileContext The evaluated context ready for storage.
     * @param IngressPolicyInterface $policy      The policy providing the scheme name and path resolver.
     *
     * @return FileRecord A record describing the persisted file and its storage URI.
     */
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

        // context -> stream
        $fileContext = Hydrator::ensureStream($fileContext);

        // stream -> write
        Storage::write($uri, $fileContext->stream());

        // context -> filerecord.
        return FileRecord::from(
            context: $fileContext,
            uri: $uri->toString(),
        );
    }
}
