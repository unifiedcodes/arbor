<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\state\FileContext;
use LogicException;

/**
 * Filters files based on their name length, rejecting any file whose name
 * exceeds the specified maximum length.
 *
 * @package Arbor\shared\filters
 */
final class FilenameLength implements FileFilterInterface
{
    /**
     * @param int $maxLength The maximum permitted number of characters in a file name.
     */
    public function __construct(
        private int $maxLength
    ) {}

    /**
     * Validates that the file name in the given context does not exceed the maximum length.
     *
     * @param FileContext $context The file context containing the file's metadata, including its name.
     *
     * @throws LogicException If the file name exceeds the maximum permitted length.
     */
    public function filter(FileContext $context)
    {
        if (mb_strlen($context->name()) > $this->maxLength) {
            throw new LogicException('File name is too long');
        }
    }
}
