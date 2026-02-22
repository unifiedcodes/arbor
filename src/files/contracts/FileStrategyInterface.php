<?php

namespace Arbor\files\contracts;

use Arbor\files\state\FileContext;

/**
 * Defines the contract for a file strategy that processes and validates a file context.
 *
 * Implementations of this interface are responsible for applying a specific strategy
 * against a given {@see FileContext}, such as resolving, verifying, or transforming
 * the context before it proceeds through the file handling pipeline.
 *
 * @package Arbor\files\contracts
 */
interface FileStrategyInterface
{
    /**
     * Process and prove the validity or state of the given file context.
     *
     * Implementations should apply their strategy logic to the provided context
     * and return the resulting — potentially modified — {@see FileContext}.
     *
     * @param  FileContext $context The file context to process.
     * @return FileContext          The processed file context after the strategy has been applied.
     */
    public function prove(FileContext $context): FileContext;
}
