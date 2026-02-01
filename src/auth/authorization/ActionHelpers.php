<?php

namespace Arbor\auth\authorization;

use LogicException;
use UnitEnum;
use BackedEnum;

trait ActionHelpers
{
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
