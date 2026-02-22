<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\state\FileContext;
use LogicException;

/**
 * Filters files based on their binary status, rejecting any file that is not binary.
 *
 * @package Arbor\shared\filters
 */
final class BinaryOnly implements FileFilterInterface
{
    /**
     * Validates that the file in the given context is a binary file.
     *
     * @param FileContext $context The file context containing the file's metadata, including its binary status.
     *
     * @throws LogicException If the file is not a binary file.
     */
    public function filter(FileContext $context)
    {
        if (! $context->isBinary()) {
            throw new LogicException('Only binary files are allowed');
        }
    }
}
