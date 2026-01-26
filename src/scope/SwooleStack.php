<?php

namespace Arbor\scope;

use Swoole\Coroutine;

final class SwooleStack implements StackInterface
{
    private const KEY = '__arbor_scope_stack';

    private function stack(): Stack
    {
        $ctx = Coroutine::getContext();

        if (!isset($ctx[self::KEY])) {
            $ctx[self::KEY] = new Stack();
        }

        return $ctx[self::KEY];
    }

    public function push(Frame $frame): void
    {
        $this->stack()->push($frame);
    }

    public function pop(): Frame
    {
        return $this->stack()->pop();
    }

    public function current(): ?Frame
    {
        return $this->stack()->current();
    }

    public function getFrame(int $index): ?Frame
    {
        return $this->stack()->getFrame($index);
    }

    public function depth(): int
    {
        return $this->stack()->depth();
    }
}
