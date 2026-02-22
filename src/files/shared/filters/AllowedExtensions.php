<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\state\FileContext;
use LogicException;

/**
 * Filters files based on their extension, rejecting any file whose extension
 * is not present in the list of allowed extensions.
 *
 * @package Arbor\shared\filters
 */
final class AllowedExtensions implements FileFilterInterface
{
    /**
     * @param array $allowedExtensions A list of permitted file extensions (e.g. ['jpg', 'png', 'pdf']).
     *                                 Comparisons are performed in a case-insensitive manner.
     */
    public function __construct(
        private array $allowedExtensions
    ) {}

    /**
     * Validates that the file extension in the given context is allowed.
     *
     * @param FileContext $context The file context containing the file's metadata, including its extension.
     *
     * @throws LogicException If the file extension is not in the list of allowed extensions.
     */
    public function filter(FileContext $context)
    {
        if (in_array(
            strtolower($context->extension()),
            $this->allowedExtensions,
            true
        )) {
            throw new LogicException('File extension is not allowed');
        }
    }
}
