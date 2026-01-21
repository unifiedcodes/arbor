<?php

namespace Arbor\container;

/**
 * Interface AttributeInterface
 *
 * Defines a contract for classes that provide attribute resolution,
 * typically used by dependency injection (DI) containers to resolve injection values.
 *
 * @package Arbor\container
 */
interface AttributeInterface
{
    /**
     * Resolves and returns the value of the attribute.
     *
     * This method is intended to be called by a DI container to retrieve
     * the value that should be injected.
     *
     * @return mixed The resolved injection value.
     */
    public function resolve(): mixed;

    public function require(): void;
}
