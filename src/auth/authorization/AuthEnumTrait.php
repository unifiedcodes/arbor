<?php

namespace Arbor\auth\authorization;

use LogicException;
use UnitEnum;
use BackedEnum;

/**
 * AuthEnumTrait
 *
 * A trait that provides action key retrieval functionality for authorization enums.
 * This trait implements the ActionInterface's key() method and enforces that it is
 * only used on backed enums, ensuring consistent behavior across authorization actions.
 *
 * @package Arbor\auth\authorization
 */
trait AuthEnumTrait
{
    /**
     * Get the key identifier for this action.
     *
     * Extracts and returns the backing value of the enum case as a string.
     * This method enforces that the trait is only used on proper backed enums
     * and will throw exceptions if used on non-enum or non-backed enum types.
     *
     * @throws LogicException If the trait is not used on a UnitEnum
     * @throws LogicException If the enum is not a BackedEnum
     *
     * @return string The string value of the enum case
     */
    public function key(): string
    {
        if (! $this instanceof UnitEnum) {
            throw new LogicException(
                'ActionHelpers can only be used on enums.'
            );
        }

        if (! $this instanceof BackedEnum) {
            throw new LogicException(
                'Action enums must be backed enums.'
            );
        }

        return (string) $this->value;
    }
}
