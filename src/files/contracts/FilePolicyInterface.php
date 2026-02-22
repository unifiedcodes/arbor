<?php

namespace Arbor\files\contracts;

/**
 * Defines the contract for a file policy that describes allowed file schemes and MIME types.
 *
 * Implementations of this interface are responsible for providing the configuration
 * rules that govern how files are handled, including supported URI schemes,
 * accepted MIME types, and policy-level options.
 *
 * @package Arbor\files\contracts
 */
interface FilePolicyInterface
{
    /**
     * Return the URI scheme associated with this policy (e.g., 'http', 'ftp', 'local').
     *
     * @return string The URI scheme identifier.
     */
    public function scheme(): string;

    /**
     * Return the list of accepted MIME types for this policy.
     *
     * @return array<int, string> An array of accepted MIME type strings.
     */
    public function mimes(): array;

    /**
     * Return a new instance of the policy with the given options applied.
     *
     * This method must be implemented immutably — the original instance
     * should remain unchanged, and a new instance with the provided options
     * should be returned.
     *
     * @param  array<string, mixed> $options The options to apply to the policy.
     * @return static                        A new instance of the implementing class with the given options.
     */
    public function withOptions(array $options): static;
}
