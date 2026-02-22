<?php

namespace Arbor\shared\filters;

use Arbor\files\contracts\FileFilterInterface;
use Arbor\files\state\FileContext;
use LogicException;

/**
 * Filters files based on their extension, rejecting any file whose extension
 * is present in the list of denied extensions.
 *
 * @package Arbor\shared\filters
 */
final class DenyExtensions implements FileFilterInterface
{
    /**
     * @param array $deniedExtensions A list of forbidden file extensions (e.g. ['php', 'exe', 'sh']).
     *                                Comparisons are performed in a case-insensitive manner.
     */
    public function __construct(
        private array $deniedExtensions
    ) {}

    /**
     * Validates that the file extension in the given context is not forbidden.
     *
     * @param FileContext $context The file context containing the file's metadata, including its extension.
     *
     * @throws LogicException If the file extension is in the list of denied extensions.
     */
    public function filter(FileContext $context)
    {
        if (in_array(
            strtolower($context->extension()),
            $this->deniedExtensions,
            true
        )) {
            throw new LogicException('File extension is forbidden');
        }
    }
}
