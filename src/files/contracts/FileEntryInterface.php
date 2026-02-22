<?php

namespace Arbor\files\contracts;

use Arbor\files\state\Payload;

/**
 * Defines the contract for a file entry that can be converted into a normalized payload.
 *
 * Implementations of this interface are responsible for accepting raw input
 * and transforming it into a standardized {@see Payload} representation.
 *
 * @package Arbor\files\contracts
 */
interface FileEntryInterface
{
    /**
     * Convert entry source into a normalized payload.
     *
     * @return Payload The normalized payload derived from the entry's source.
     */
    public function toPayload(): Payload;

    /**
     * Return a new instance of the entry with the given input applied.
     *
     * This method must be implemented immutably — the original instance
     * should remain unchanged, and a new instance with the provided input
     * should be returned.
     *
     * @param  mixed  $input The raw input to associate with the entry.
     * @return static        A new instance of the implementing class with the given input.
     */
    public function withInput(mixed $input): static;
}
