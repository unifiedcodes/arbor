<?php

namespace Arbor\collections;

use LogicException;

class Collector
{
    protected Collections $collections;
    protected Groups $groups;

    public function __construct()
    {
        $this->collections = new Collections();
        $this->groups = new Groups();
    }

    public function slot(
        string $key,
        string $type,
        bool $isMultiple = true
    ): self {
        $this->collections->define($key, $type, $isMultiple);
        return $this;
    }

    public function group(string $key): self
    {
        $this->groups->define($key);
        return $this;
    }

    public function add(string $key, mixed $value): self
    {
        $this->collections->add($key, $value);
        return $this;
    }

    public function bind(string $groupName, ...$collections): self
    {
        $this->groups->add($groupName, ...$collections);
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

        if ($this->collections->has($key)) {
            foreach ($this->collections->touch($key) as $value) {
                $result[] = $value;
            }
        }
    }

    public function finalize(): void
    {
        $this->collections->finalize();
        $this->groups->finalize();
    }
}
