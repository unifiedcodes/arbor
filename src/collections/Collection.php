<?php

namespace Arbor\collections;

use LogicException;
use InvalidArgumentException;
use Arbor\validation\Validate;


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
                'Single Mode Collection already contains an instance or value for this key'
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

        $validation = Validate::check($value, $this->type);

        if (!$validation->isValid()) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s collection expects instance of [%s], [%s] given. Validation error: %s',
                    $this->key,
                    $this->type,
                    get_debug_type($value),
                    $validation->errors()
                )
            );
        }
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
