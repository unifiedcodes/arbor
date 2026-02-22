<?php

namespace Arbor\files\contracts;

use Arbor\files\state\FileContext;

/**
 * Defines the contract for a file filter that evaluates or processes a file context.
 *
 * Implementations of this interface are responsible for applying filtering logic
 * against a given {@see FileContext}, such as validating, excluding, or transforming
 * file entries based on specific criteria.
 *
 * @package Arbor\files\contracts
 */
interface FileFilterInterface
{
    /**
     * Apply the filter logic against the given file context.
     *
     * @param  FileContext $context The file context to evaluate or process.
     * @return mixed
     */
    public function filter(FileContext $context);
}
