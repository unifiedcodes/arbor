<?php

namespace Arbor\files\contracts;

use Arbor\files\state\FileContext;

/**
 * Defines the contract for a variant profile that describes how a file variant should be generated.
 *
 * Implementations of this interface are responsible for providing the configuration
 * needed to produce a specific variant of a file — such as a thumbnail, preview, or
 * alternate format — including its naming convention, filters, transformers, and storage path.
 *
 * @package Arbor\files\contracts
 */
interface VariantProfileInterface
{
    /**
     * Return the suffix to append to the file name for this variant.
     *
     * @return string The name suffix that distinguishes this variant (e.g., '_thumb', '_preview').
     */
    public function nameSuffix(): string;

    /**
     * Resolve the list of filters to apply for the given file context.
     *
     * @param  FileContext                $context The file context to evaluate.
     * @return array<FileFilterInterface>          An array of filters to run against the context.
     */
    public function filters(FileContext $context): array;

    /**
     * Resolve the list of transformers to apply for the given file context.
     *
     * @param  FileContext                    $context The file context to evaluate.
     * @return array<FileTransformerInterface>         An array of transformers to run against the context.
     */
    public function transformers(FileContext $context): array;

    /**
     * Return the storage path where this variant should be saved.
     *
     * @return string The destination path for the generated variant.
     */
    public function path(): string;
}
