<?php

namespace Arbor\scope;


interface StackInterface
{
    public function push(Frame $frame): void;

    public function pop(): Frame;

    public function current(): ?Frame;

    public function getFrame(int $index): ?Frame;

    public function depth(): int;

    // public function main(): Frame;

    // public function parent(): Frame;
}
