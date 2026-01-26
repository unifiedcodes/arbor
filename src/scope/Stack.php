<?php

namespace Arbor\scope;


final class Stack implements StackInterface
{
    private array $frames = [];

    public function push(Frame $frame): void
    {
        $this->frames[] = $frame;
    }

    public function pop(): Frame
    {
        if (empty($this->frames)) {
            throw new \RuntimeException('Scope stack underflow');
        }

        return array_pop($this->frames);
    }

    public function current(): ?Frame
    {
        return $this->frames[array_key_last($this->frames)] ?? null;
    }

    public function getFrame(int $index): ?Frame
    {
        return $this->frames[$index] ?? null;
    }

    public function depth(): int
    {
        return count($this->frames);
    }
}
