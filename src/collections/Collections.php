<?php

namespace Arbor\collections;

use LogicException;
use InvalidArgumentException;
use Override;

class Collections extends Collectible
{
    public function type(): string
    {
        return 'Collection';
    }

    protected function dto(): string
    {
        return CollectionEntry::class;
    }
}
