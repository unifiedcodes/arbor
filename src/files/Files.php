<?php

namespace Arbor\files;


use Arbor\files\contracts\FilePolicyInterface;
use Arbor\files\contracts\FileRecordStoreInterface;
use Arbor\files\contracts\FileEntryInterface;
use Arbor\files\Keeper;
use Arbor\files\Filer;
use Arbor\files\state\FileRecord;
use Arbor\storage\Uri;


/**
 * Primary facade for file management operations, composing ingress, persistence,
 * and variant generation into a unified API.
 *
 * Files owns and wires together the internal collaborators responsible for each
 * concern — {@see Filer} for the ingress pipeline, {@see Keeper} for record
 * persistence, {@see Variator} for variant generation, and {@see PolicyCatalog}
 * for policy registration and resolution — and exposes a minimal surface for
 * consumer code to save files and manage variants without interacting with any
 * of those subsystems directly.
 *
 * @package Arbor\files
 */
class Files
{
    private PolicyCatalog $policyCatalog;
    private Filer $filer;
    private Keeper $filesKeeper;
    private Variator $variator;


    /**
     * @param FileEntryInterface        $fileEntry   Normalises raw file input into a transferable payload.
     * @param FileRecordStoreInterface|null $recordStore Optional store for persisting FileRecord instances;
     *                                                   defaults to a no-op store if null.
     */
    public function __construct(
        private FileEntryInterface $fileEntry,
        private ?FileRecordStoreInterface $recordStore = null
    ) {
        $this->policyCatalog = new PolicyCatalog();

        $this->filer = new Filer($fileEntry, $this->policyCatalog);
        $this->filesKeeper = new Keeper($recordStore);
        $this->variator = new Variator($this->policyCatalog);
    }


    /**
     * Registers one or more policies with the internal policy catalog.
     *
     * Policies must be registered before any save or resolve operation is
     * performed for the scheme and interface they govern.
     *
     * @param array<FilePolicyInterface> $policies Policy instances to register.
     *
     * @return void
     */
    public function registerPolicies(array $policies)
    {
        $this->policyCatalog->registerPolicies($policies);
    }


    /**
     * Resolves the policy registered for the given interface, scheme, and MIME type.
     *
     * Delegates directly to the internal {@see PolicyCatalog}, allowing consumers
     * to retrieve a policy instance for manual inspection or use outside of the
     * standard save pipeline.
     *
     * @param string $interfaceFqn Fully-qualified interface name the policy must implement (e.g. IngressPolicyInterface::class).
     * @param string $scheme       The storage scheme identifier (e.g. "avatars", "documents").
     * @param string $mime         The MIME type of the file being processed (e.g. "image/png").
     * @param array  $options      Optional overrides to apply to the resolved policy.
     *
     * @return FilePolicyInterface The resolved and configured policy instance.
     */
    public function resolvePolicy(string $interfaceFqn, string $scheme, string $mime, array $options = []): FilePolicyInterface
    {
        return $this->policyCatalog->resolvePolicy(
            $interfaceFqn,
            $scheme,
            $mime,
            $options
        );
    }


    /**
     * Saves a file through the full ingress pipeline and persists the resulting record.
     *
     * The raw input is processed by the {@see Filer} (normalisation, proving,
     * evaluation, and storage), and the produced {@see FileRecord} is then handed
     * to the {@see Keeper} for record persistence before being returned.
     *
     * @param string $scheme  The storage scheme identifier used to resolve the ingress policy.
     * @param mixed  $input   The raw file input accepted by the configured {@see FileEntryInterface}.
     * @param array  $options Optional overrides forwarded to the resolved policy.
     *
     * @return FileRecord The persisted record describing the saved file.
     */
    public function save(string $scheme, mixed $input, array $options = []): FileRecord
    {
        // consume options needed before policy resolution
        $fileRecord = $this->filer->save($scheme, $input, $options);

        $fileRecord = $this->filesKeeper->save($fileRecord);

        return $fileRecord;
    }


    /**
     * Saves a file and generates variants, returning a record with variants attached.
     *
     * Behaves identically to {@see self::save()} for the primary file, then passes
     * the stored URI to {@see self::createVariations()} and attaches the resulting
     * variant records to the returned {@see FileRecord}.
     *
     * @param string $scheme  The storage scheme identifier used to resolve the ingress policy.
     * @param mixed  $input   The raw file input accepted by the configured {@see FileEntryInterface}.
     * @param array  $options Optional overrides forwarded to the resolved policy.
     *
     * @return FileRecord The persisted record with variant records attached.
     */
    public function saveWithVariants(string $scheme, mixed $input, array $options = []): FileRecord
    {
        $fileRecord = $this->save($scheme, $input, $options);

        $variants = $this->createVariations($fileRecord->uri);

        return $fileRecord->withVariants($variants);
    }


    /**
     * Generates variants for an already-stored file identified by its URI.
     *
     * Delegates to the internal {@see Variator}, which resolves the appropriate
     * variant policy and produces a map of {@see VariantRecord} instances.
     *
     * @param string|Uri $uri The URI of the stored file for which variants should be generated.
     *
     * @return array<string,\Arbor\files\state\VariantRecord> A map of named variant records.
     */
    public function createVariations(string|Uri $uri): array
    {
        return $this->variator->generate($uri);
    }
}
