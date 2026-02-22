<?php

namespace Arbor\files\contracts;

use Arbor\files\state\FileContext;

/**
 * Defines the contract for a file transformer that derives or mutates a file from a trusted context.
 *
 * Implementations of this interface are responsible for applying transformation logic
 * against a given {@see FileContext} — such as resizing, converting, compressing, or
 * otherwise processing the file — and returning a new context reflecting the result.
 *
 * @package Arbor\files\contracts
 */
interface FileTransformerInterface
{
    /**
     * Mutates or derives a file from a trusted context.
     *
     * Implementations must not modify the original {@see FileContext} instance.
     * A new {@see FileContext} representing the transformed state must always be returned.
     *
     * @param  FileContext $context The trusted file context to transform.
     * @return FileContext          A new file context reflecting the transformed state.
     */
    public function transform(FileContext $context): FileContext;
}
