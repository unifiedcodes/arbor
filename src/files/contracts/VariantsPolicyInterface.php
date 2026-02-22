<?php

namespace Arbor\files\contracts;

use Arbor\files\state\FileContext;

/**
 * Defines the contract for a variants policy that governs how file variants are generated and stored.
 *
 * Extends {@see FilePolicyInterface} to provide variant-specific behaviour, including
 * the resolution of variant profiles and the destination path for variants derived
 * from a given {@see FileContext}.
 *
 * @package Arbor\files\contracts
 */
interface VariantsPolicyInterface extends FilePolicyInterface
{
    /**
     * Resolve the list of variant profiles to generate for the given file context.
     *
     * @param  FileContext                   $record The file context to evaluate.
     * @return array<VariantProfileInterface>        An array of variant profiles to apply against the context.
     */
    public function variants(FileContext $record): array;

    /**
     * Resolve the destination path for the variants derived from the given file context.
     *
     * @param  FileContext $record The file context to evaluate.
     * @return string              The resolved destination path for the generated variants.
     */
    public function path(FileContext $record): string;
}
