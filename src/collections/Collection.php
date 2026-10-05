<?php

namespace Arbor\collections;

use BadMethodCallException;
use LogicException;
use InvalidArgumentException;


class Collection
{
    protected array $values = [];

    public function __construct(
        protected string $key,
        protected ?string $type = null,
        protected bool $isMultiple = false
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function type(): ?string
    {
        return $this->type;
    }

    public function isMultiple(): bool
    {
        return $this->isMultiple;
    }

    public function push(mixed $value): static
    {
        if (!$this->isMultiple && count($this->values) >= 1) {
            throw new LogicException(
                'Single Mode Collection already contains an instance.'
            );
        }

        $this->validateType($value);
        $this->values[] = $value;

        return $this;
    }

    protected function validateType(mixed $value): void
    {
        if ($this->type === null) {
            return;
        }

        $type = $this->type;

        // Class / interface
        if (class_exists($type) || interface_exists($type)) {
            if (!$value instanceof $type) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Collection expects instance of [%s], [%s] given.',
                        $type,
                        get_debug_type($value)
                    )
                );
            }

            return;
        }

        // Delegate built-in validation to Arbor validation module.
    }

    public function get(): mixed
    {
        if (empty($this->values)) {
            return null;
        }

        if (!$this->isMultiple) {
            return $this->values[0];
        }

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
