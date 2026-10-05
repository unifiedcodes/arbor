<?php

namespace Arbor\collections;

class Collector
{
    protected $collections = [];

    public function define(string $key, string $type, bool $isMultiple = false)
    {
        if ($this->has($key)) {
            throw new LogicException(
                "Collection with key [{$key}] is already defined."
            );
        }

        $this->collections[$key] = new Collection(
            key: $key,
            type: $type,
            isMultiple: $isMultiple
        );
    }

    public function __call(string $method, array $arguments): mixed
    {
        $this->ensureKey($method);

        if (count($arguments) === 0) {
            return $this->collections[$method]->get();
        }

        if (count($arguments) === 1) {
            $this->collections[$method]->push($arguments[0]);

            return $this;
        }

        throw new BadMethodCallException(
            "Collection accessor [{$method}] accepts zero or one argument."
        );
    }

    public function has(string $key): bool
    {
        return isset($this->collections[$key]);
    }

    protected function ensureKey(string $key): void
    {
        if (!$this->has($key)) {
            throw new InvalidArgumentException(
                "Collection with key [{$key}] is not defined."
            );
        }
    }

    public function push(string $key, mixed $value): static
    {
        $this->ensureKey($key);
        $this->collections[$key]->push($value);

        return $this;
    }

    public function get(string $key): mixed
    {
        $this->ensureKey($key);
        $this->collections[$key]->get($key);

        return $this;
    }

    public function clear(?string $key = null): bool
    {
        $this->ensureKey($key);

        if ($key === null) {
            $this->collections = [];

            return true;
        }

        unset($this->collections[$key]);

        return true;
    }

    public function getCollection(string $key): Collection
    {
        $this->ensureKey($key);
        return $this->collections[$key];
    }

    public function self()
    {
        print_r($this);
    }
}
