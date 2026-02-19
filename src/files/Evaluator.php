<?php

namespace Arbor\files;

use Arbor\files\ingress\FileContext;
use RuntimeException;


final class Evaluator
{
    /**
     * Execute filters (fail-fast).
     */
    public static function filters(array $filters, FileContext $subject): void
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
    public static function transformers(array $transformers, FileContext $subject): mixed
    {
        foreach ($transformers as $transformer) {
            $subject = $transformer->transform($subject);
        }

        return $subject;
    }
}
