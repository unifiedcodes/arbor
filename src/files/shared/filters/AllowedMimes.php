<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\state\FileContext;
use LogicException;

/**
 * Filters files based on their MIME type, rejecting any file whose MIME type
 * is not present in the list of allowed MIME types.
 *
 * @package Arbor\shared\filters
 */
final class AllowedMimes implements FileFilterInterface
{
    /**
     * @param array $allowedMime A list of permitted MIME types (e.g. ['image/jpeg', 'image/png', 'application/pdf']).
     */
    public function __construct(
        private array $allowedMime
    ) {}

    /**
     * Validates that the MIME type of the file in the given context is allowed.
     *
     * @param FileContext $context The file context containing the file's metadata, including its MIME type.
     *
     * @throws LogicException If the file MIME type is not in the list of allowed MIME types.
     */
    public function filter(FileContext $context)
    {
        if (! in_array(
            $context->mime(),
            $this->allowedMime,
            true
        )) {
            throw new LogicException('File MIME type is not allowed');
        }
    }
}
