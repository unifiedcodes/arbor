<?php

namespace Arbor\files\utilities;

use Arbor\files\contracts\FilePolicyInterface;
use Arbor\support\Configuration;


/**
 * Abstract base class for file policy implementations.
 *
 * Provides a standard construction and option-merging pattern for policies that
 * govern file handling behaviour (e.g. allowed MIME types, size limits, naming
 * rules). Concrete subclasses declare their default option set via the
 * {@see Configuration} trait and implement the policy contract defined by
 * {@see FilePolicyInterface}.
 *
 * Options are applied at construction time and may be overridden on a per-call
 * basis via {@see self::withOptions()}, which returns a configured clone without
 * mutating the original instance.
 *
 * @package Arbor\files\utilities
 */
abstract class BaseFilePolicy implements FilePolicyInterface
{
    use Configuration;

    /**
     * Initialises the policy with the given options merged over the subclass defaults.
     *
     * @param array $options Option overrides to apply on top of the default configuration.
     */
    public function __construct(array $options = [])
    {
        $this->applyDefaults($options);
    }


    /**
     * Returns a clone of this policy with the given options merged over its current defaults.
     *
     * The original instance is left unchanged, preserving value semantics and
     * allowing a single base policy to be cheaply specialised for different contexts.
     *
     * @param array $options Option overrides to apply to the cloned instance.
     *
     * @return static A new instance of the same policy class with the merged options applied.
     */
    public function withOptions(array $options = []): static
    {
        $clone = clone $this;

        $clone->options = $clone->mergeDefaults($options);

        return $clone;
    }
}
