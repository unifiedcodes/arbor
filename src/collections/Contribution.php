<?php

namespace Arbor\collections;

final class Contribution
{
    public function __construct(
        public readonly string $key,
        public readonly mixed $value,
        public readonly ?string $type = null,
        public readonly ?string $source = null,
    ) {}
}
