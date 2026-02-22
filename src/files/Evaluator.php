<?php

namespace Arbor\files;

use Arbor\files\state\FileContext;
use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\contracts\FileTransformerInterface;


/**
 * Stateless utility for executing ordered pipelines of filters and transformers
 * against a {@see FileContext}.
 *
 * Filters and transformers are intentionally kept as separate concerns and
 * invoked through distinct methods to make the fail-fast vs. reducer semantics
 * explicit at the call site.
 *
 * @package Arbor\files
 */
final class Evaluator
{
    /**
     * Executes an ordered list of filters against the given FileContext.
     *
     * Filters are applied sequentially and are expected to throw on violation,
     * making this a fail-fast pipeline — execution halts as soon as any filter
     * rejects the subject.
     *
     * @param array<int,FileFilterInterface> $filters An ordered list of filter instances to apply.
     * @param FileContext                                            $subject The file context to validate.
     *
     * @return void
     */
    public static function filters(array $filters, FileContext $subject): void
    {
        foreach ($filters as $filter) {
            $filter->filter($subject);
        }
    }

    /**
     * Executes an ordered list of transformers against the given FileContext,
     * threading the result of each transformer into the next (reducer pattern).
     *
     * Each transformer receives the FileContext returned by the previous one,
     * allowing the context to be progressively enriched or modified as it passes
     * through the pipeline.
     *
     * @param array<int,FileTransformerInterface> $transformers An ordered list of transformer instances to apply.
     * @param FileContext                                                 $subject      The initial file context to transform.
     *
     * @return FileContext The final transformed FileContext after all transformers have been applied.
     */
    public static function transformers(array $transformers, FileContext $subject): FileContext
    {
        foreach ($transformers as $transformer) {
            $subject = $transformer->transform($subject);
        }

        return $subject;
    }
}
