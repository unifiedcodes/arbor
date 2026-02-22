<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\state\FileContext;
use LogicException;

/**
 * Filters files based on their binary status, rejecting any file that is binary.
 *
 * @package Arbor\shared\filters
 */
final class TextOnlyFilter implements FileFilterInterface
{
    /**
     * Validates that the file in the given context is a text file.
     *
     * @param FileContext $context The file context containing the file's metadata, including its binary status.
     *
     * @throws LogicException If the file is a binary file.
     */
    public function filter(FileContext $context)
    {
        if ($context->isBinary()) {
            throw new LogicException('Binary files are not allowed');
        }
    }
}
