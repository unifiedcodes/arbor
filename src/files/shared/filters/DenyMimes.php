<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\state\FileContext;
use LogicException;

/**
 * Filters files based on their MIME type, rejecting any file whose MIME type
 * is present in the list of denied MIME types.
 *
 * @package Arbor\shared\filters
 */
final class DenyMimes implements FileFilterInterface
{
    /**
     * @param array $deniedMimes A list of forbidden MIME types (e.g. ['application/x-php', 'application/x-executable']).
     */
    public function __construct(
        private array $deniedMimes
    ) {}

    /**
     * Validates that the MIME type of the file in the given context is not forbidden.
     *
     * @param FileContext $context The file context containing the file's metadata, including its MIME type.
     *
     * @throws LogicException If the file MIME type is in the list of denied MIME types.
     */
    public function filter(FileContext $context)
    {
        if (in_array(
            $context->mime(),
            $this->deniedMimes,
            true
        )) {
            throw new LogicException('File MIME type is forbidden');
        }
    }
}
