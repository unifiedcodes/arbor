<?php

namespace Arbor\files;


use RuntimeException;


final class Evaluator
{
    /**
     * Execute filters (fail-fast).
     */
    public static function filters(iterable $filters, mixed $subject): void
    {
        foreach ($filters as $filter) {
            if (!$filter->filter($subject)) {
                throw new RuntimeException($filter->errorMessage($subject));
            }
        }
    }

    /**
     * Execute transformers (reducer).
     */
    public static function transformers(iterable $transformers, mixed $subject): mixed
    {
        foreach ($transformers as $transformer) {
            $subject = $transformer->transform($subject);
        }

        return $subject;
    }
}
