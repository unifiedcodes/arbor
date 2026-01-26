<?php

namespace Arbor\scope;

use RuntimeException;


final class Scope
{
    public function __construct(
        private StackInterface $stack
    ) {}

    public function enter(): void
    {
        $this->stack->push(new Frame());
    }

    public function leave(): void
    {
        $this->stack->pop();
    }

    public function set(string $key, mixed $value): void
    {
        $frame = $this->stack->current();

        if (!$frame) {
            throw new RuntimeException('No active scope frame');
        }

        $frame->set($key, $value);
    }

    public function has(string $key): bool
    {
        $frame = $this->stack->current();
        return $frame ? $frame->has($key) : false;
    }

    public function get(string $key): mixed
    {
        $frame = $this->stack->current();
        return $frame ? $frame->get($key) : null;
    }

    public function getFrame(int $index): ?Frame
    {
        return $this->stack->getFrame($index);
    }

    public function depth(): int
    {
        return $this->stack->depth();
    }
}
