<?php

namespace Arbor\view\presets;

use Arbor\view\Document;
use InvalidArgumentException;
use Closure;

/**
 * A preset implementation backed by a Closure.
 *
 * Allows defining document presets using inline anonymous functions
 * instead of dedicated preset classes.
 *
 * The provided closure:
 * - Receives a Document instance
 * - Must mutate the document
 * - Must NOT return any value
 */
final class ClosurePreset implements PresetInterface
{
    /**
     * Closure that receives a Document and returns void.
     *
     * @var Closure(Document): void
     */
    private $callback;

    /**
     * @param Closure $callback Closure that accepts a Document instance.
     */
    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Apply the closure to the document.
     *
     * @param Document $document
     *
     * @throws InvalidArgumentException If the closure returns a value.
     */
    public function apply(Document $document): void
    {
        $result = ($this->callback)($document);

        if ($result !== null) {
            throw new InvalidArgumentException(
                'Preset callable must not return a value.'
            );
        }
    }
}
