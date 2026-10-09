<?php

namespace Arbor\collections;

use InvalidArgumentException;

final class GroupEntry implements EntryInterface
{
    protected array $values = [];

    public function __construct(
        protected string $key
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function push(mixed $value): static
    {
        if (!is_string($value) || $value === '') {
            throw new InvalidArgumentException(
                'Group entries must be non-empty collection keys.'
            );
        }

        if (in_array($value, $this->values, true)) {
            return $this;
        }

        $this->values[] = $value;

        return $this;
    }

    public function get(): array
    {
        return $this->values;
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function clear(): static
    {
        $this->values = [];

        return $this;
    }
}
