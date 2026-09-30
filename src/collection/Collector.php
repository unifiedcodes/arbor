<?php

namespace Arbor\collection;


class Collector
{
    protected array $items = [];

    public function add(string $key, mixed $value): static
    {
        $this->items[$key][] = $value;
        return $this;
    }

    public function addMany(string $key, array $values): static
    {
        foreach ($values as $value) {
            $this->add($key, $value);
        }

        return $this;
    }

    public function get(string $key): array
    {
        return $this->items[$key] ?? [];
    }

    public function has(string $key): bool
    {
        return isset($this->items[$key]);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function clear(?string $key = null): static
    {
        if ($key === null) {
            $this->items = [];
        } else {
            unset($this->items[$key]);
        }

        return $this;
    }
}
