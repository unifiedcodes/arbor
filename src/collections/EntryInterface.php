<?php

namespace Arbor\collections;

interface EntryInterface
{
    public function key(): string;

    public function push(mixed $value): static;

    public function get(): array;

    public function count(): int;

    public function clear(): static;
}
