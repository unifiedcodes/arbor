<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\state\FileContext;
use LogicException;

/**
 * Filters files based on their size, rejecting any file whose size
 * is below the specified minimum number of bytes.
 *
 * @package Arbor\shared\filters
 */
final class MinFileSize implements FileFilterInterface
{
    /**
     * @param int $minBytes The minimum permitted file size in bytes.
     */
    public function __construct(
        private int $minBytes
    ) {}

    /**
     * Validates that the file size in the given context meets the minimum required size.
     *
     * @param FileContext $context The file context containing the file's metadata, including its size.
     *
     * @throws LogicException If the file size is below the minimum permitted size.
     */
    public function filter(FileContext $context)
    {
        if ($context->size() < $this->minBytes) {
            throw new LogicException('File size is too small');
        }
    }
}
