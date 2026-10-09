<?php

namespace Arbor\collections;

use LogicException;
use InvalidArgumentException;

class Groups extends Collectible
{
    public function type(): string
    {
        return 'Group';
    }

    protected function dto(): string
    {
        return GroupEntry::class;
    }
}
