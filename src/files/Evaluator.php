<?php

namespace Arbor\files;

use Arbor\files\state\FileContext;


final class Evaluator
{
    /**
     * Execute filters (fail-fast).
     */
    public static function filters(array $filters, FileContext $subject): void
    {
        foreach ($filters as $filter) {
            $filter->filter($subject);
        }
    }

    /**
     * Execute transformers (reducer).
     */
    public static function transformers(array $transformers, FileContext $subject): mixed
    {
        foreach ($transformers as $transformer) {
            $subject = $transformer->transform($subject);
        }

        return $subject;
    }
}
