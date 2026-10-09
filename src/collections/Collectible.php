<?php

namespace Arbor\collections;

use LogicException;
use InvalidArgumentException;

class Collectible
{
    /** @var array<string, EntryInterface> */
    protected array $entries = [];

    /** @var array<string, list<Contribution>> */
    protected array $contributions = [];


    public function __construct(
        protected string $type,
        protected string $dto
    ) {}

    public function type()
    {
        return $this->type;
    }

    public function dto()
    {
        return $this->dto;
    }

    public function define(string $key, mixed ...$arguments): static
    {
        if ($this->has($key)) {
            throw new LogicException(
                sprintf(
                    "%s with key [%s] is already defined.",
                    $this->type(),
                    $key
                )
            );
        }

        $this->entries[$key] = new ($this->dto())($key, ...$arguments);

        $this->resolveContributions($key);

        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->entries[$key]);
    }

    public function ensureKey(string $key): void
    {
        if (!$this->has($key)) {
            throw new InvalidArgumentException(
                sprintf(
                    "%s with key [%s] is not defined.",
                    $this->type(),
                    $key
                )
            );
        }
    }

    public function add(string $key, mixed ...$value): static
    {
        foreach ($value as $value) {
            if ($this->has($key)) {
                $this->push($key, $value);
                continue;
            }

            $this->contribute($key, $value);
        }

        return $this;
    }

    protected function push(string $key, mixed $value): static
    {
        $this->ensureKey($key);
        $this->entries[$key]->push($value);

        return $this;
    }

    protected function contribute(
        string $key,
        mixed $value
    ): static {
        $this->contributions[$key][] = new Contribution(
            key: $key,
            value: $value,
            type: $this->type()
        );

        return $this;
    }

    protected function resolveContributions(string $key): void
    {
        if (!isset($this->contributions[$key])) {
            return;
        }

        while (!empty($this->contributions[$key])) {
            $contribution = $this->contributions[$key][0];

            $this->push(
                $contribution->key,
                $contribution->value
            );

            array_shift($this->contributions[$key]);
        }

        unset($this->contributions[$key]);
    }

    public function finalize(): void
    {
        if (empty($this->contributions)) {
            return;
        }

        $keys = implode(', ', array_keys($this->contributions));

        throw new LogicException(
            sprintf(
                'Unresolved %s contributions: [%s].',
                $this->type(),
                $keys
            )
        );
    }

    public function get(string $key): array
    {
        $this->ensureKey($key);
        return $this->touch($key);
    }

    public function touch(string $key): array
    {
        return $this->entries[$key]->get();
    }
}
