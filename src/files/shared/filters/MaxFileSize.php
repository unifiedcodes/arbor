<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\state\FileContext;
use LogicException;

/**
 * Filters files based on their size, rejecting any file whose size
 * exceeds the specified maximum number of bytes.
 *
 * @package Arbor\shared\filters
 */
final class MaxFileSize implements FileFilterInterface
{
    /**
     * @param int $maxBytes The maximum permitted file size in bytes.
     */
    public function __construct(
        private int $maxBytes
    ) {}

    /**
     * Validates that the file size in the given context does not exceed the maximum allowed size.
     *
     * @param FileContext $context The file context containing the file's metadata, including its size.
     *
     * @throws LogicException If the file size exceeds the maximum permitted size.
     */
    public function filter(FileContext $context)
    {
        if ($context->size() > $this->maxBytes) {
            throw new LogicException('File size exceeds allowed limit');
        }
    }
}
