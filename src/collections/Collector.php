<?php

namespace Arbor\collections;

use LogicException;
use InvalidArgumentException;

class Collector
{
    protected $collections = [];
    protected $groups = [];

    public function __call(string $method, array $arguments): mixed
    {
        if ($this->hasGroup($method)) {
            return $this->handleGroupCall($method, $arguments);
        }

        if ($this->has($method)) {
            return $this->handleCollectionCall($method, $arguments);
        }

        throw new InvalidArgumentException(
            "Collection or group with key [{$method}] is not defined."
        );
    }

    protected function handleGroupCall(
        string $method,
        array $arguments
    ): mixed {
        if (count($arguments) === 0) {
            return $this->getGroup($method);
        }

        return $this->pushToGroup($method, ...$arguments);
    }

    protected function handleCollectionCall(
        string $method,
        array $arguments
    ): mixed {
        if (count($arguments) === 0) {
            return $this->get($method);
        }

        return $this->push($method, $arguments[0]);
    }

    public function define(
        string $key,
        string $type,
        bool $isMultiple = false
    ): static {
        if ($this->hasSome($key)) {
            throw new LogicException(
                "Collection or group with name [{$key}] is already defined."
            );
        }

        $this->collections[$key] = new Collection(
            key: $key,
            type: $type,
            isMultiple: $isMultiple
        );

        return $this;
    }

    public function group(string $groupName, ...$collections)
    {
        if ($this->hasSome($groupName)) {
            throw new LogicException(
                "Collection or group with name [{$groupName}] is already defined."
            );
        }

        $this->groups[$groupName] = [];

        $this->pushToGroup($groupName, ...$collections);

        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->collections[$key]);
    }

    public function hasGroup(string $groupName): bool
    {
        return isset($this->groups[$groupName]);
    }

    public function hasSome(string $key): bool
    {
        return $this->has($key) || $this->hasGroup($key);
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

    public function pushToGroup(string $groupName, ...$collections): static
    {
        if (!$this->hasGroup($groupName)) {
            throw new InvalidArgumentException(
                "Group with name [{$groupName}] is not defined."
            );
        }

        foreach ($collections as $collection) {
            $this->ensureKey($collection);
            $this->groups[$groupName][] = $collection;
        }

        return $this;
    }

    public function get(string $key): array
    {
        $this->ensureKey($key);
        return $this->collections[$key]->get();
    }

    public function getCollection(string $key): Collection
    {
        $this->ensureKey($key);
        return $this->collections[$key];
    }

    public function keysInGroup(string $groupName): array
    {
        if (!$this->hasGroup($groupName)) {
            throw new InvalidArgumentException(
                "Group with name: [{$groupName}] is not defined."
            );
        }

        return $this->groups[$groupName];
    }

    public function getGroup(string $groupName)
    {
        return $this->concat(...$this->keysInGroup($groupName));
    }

    public function concat(string ...$keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $this->ensureKey($key);

            $result = array_merge_recursive(
                $result,
                $this->get($key)
            );
        }

        return $result;
    }

    public function clear(?string $key = null): bool
    {
        if ($key === null) {
            foreach ($this->collections as $collection) {
                $collection->clear();
            }

            return true;
        }

        $this->ensureKey($key);
        $this->collections[$key]->clear();

        return true;
    }
}
