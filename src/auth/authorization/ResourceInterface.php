<?php

namespace Arbor\auth\authorization;

/**
 * ResourceInterface
 *
 * An interface for defining authorization resources. Implementing classes or enums
 * must provide a way to retrieve the resource's key identifier.
 *
 * @package Arbor\auth\authorization
 */
interface ResourceInterface
{
    /**
     * Get the key identifier for this resource.
     *
     * Returns a string representation of the resource that can be used for
     * authorization checks and ability matching.
     *
     * @return string The resource's key identifier
     */
    public function key(): string;
}
