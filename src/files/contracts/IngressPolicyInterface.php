<?php

namespace Arbor\files\contracts;

use Arbor\files\state\FileContext;

/**
 * Defines the contract for an ingress policy that governs how incoming files are received and processed.
 *
 * Extends {@see FilePolicyInterface} to provide ingress-specific behaviour, including
 * the resolution of processing strategies, filters, transformers, and the destination
 * path for incoming files based on a given {@see FileContext}.
 *
 * @package Arbor\files\contracts
 */
interface IngressPolicyInterface extends FilePolicyInterface
{
    /**
     * Resolve the file strategy to apply for the given file context.
     *
     * @param  FileContext           $context The file context to evaluate.
     * @return FileStrategyInterface          The strategy responsible for proving the file context.
     */
    public function strategy(FileContext $context): FileStrategyInterface;

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
     * Resolve the destination path for the file described by the given context.
     *
     * @param  FileContext $context The file context to evaluate.
     * @return string               The resolved destination path for the incoming file.
     */
    public function path(FileContext $context): string;
}
