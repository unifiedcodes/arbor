<?php

namespace Arbor\collections;

use LogicException;

class Collector
{
    protected Collectible $slots;
    protected Collectible $groups;

    public function __construct()
    {
        $this->slots = new Collectible('Slots', Slot::class);
        $this->groups = new Collectible('Group', Group::class);
    }

    public function slot(
        string $key,
        string $type,
        bool $isMultiple = true
    ): self {
        $this->slots->define($key, $type, $isMultiple);
        return $this;
    }

    public function add(string $key, mixed $value): self
    {
        $this->slots->add($key, $value);
        return $this;
    }

    public function group(string $key, ?array $values = null): self
    {
        $this->groups->define($key);

        if (!empty($values)) {
            $this->bind($key, ...$values);
        }

        return $this;
    }

    public function bind(string $groupName, ...$slots): self
    {
        $this->groups->add($groupName, ...$slots);
        return $this;
    }

    public function get(string ...$keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $this->resolve($key, $result, []);
        }

        return $result;
    }

    private function resolve(
        string $key,
        array &$result,
        array $path
    ): void {
        if ($this->groups->has($key)) {
            if (isset($path[$key])) {
                $cycle = implode(' -> ', [...array_keys($path), $key]);

                throw new LogicException(
                    "Circular group reference detected: [{$cycle}]."
                );
            }

            $path[$key] = true;

            foreach ($this->groups->touch($key) as $collectionKey) {
                $this->resolve($collectionKey, $result, $path);
            }

            return;
        }

        if ($this->slots->has($key)) {
            foreach ($this->slots->touch($key) as $value) {
                $result[] = $value;
            }
        }
    }

    public function finalize(): void
    {
        $this->slots->finalize();
        $this->groups->finalize();
    }
}
