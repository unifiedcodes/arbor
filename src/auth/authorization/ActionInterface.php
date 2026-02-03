<?php

namespace Arbor\auth\authorization;

/**
 * ActionInterface
 *
 * An interface for defining authorization actions. Implementing classes or enums
 * must provide a way to retrieve the action's key identifier.
 *
 * @package Arbor\auth\authorization
 */
interface ActionInterface
{
    /**
     * Get the key identifier for this action.
     *
     * Returns a string representation of the action that can be used for
     * authorization checks and policy matching.
     *
     * @return string The action's key identifier
     */
    public function key(): string;
}
