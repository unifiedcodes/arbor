<?php

namespace Arbor\scope;

use Swoole\Coroutine;

/**
 * Swoole coroutine-aware stack implementation for managing scope frames.
 *
 * This class implements a stack that stores scope frames in Swoole's coroutine context,
 * ensuring that each coroutine has its own isolated stack of frames. This allows for
 * proper scope management in asynchronous, coroutine-based applications.
 */
final class SwooleStack implements StackInterface
{
    /**
     * The key used to store the stack instance in the coroutine context.
     *
     * @var string
     */
    private const KEY = '__arbor_scope_stack';

    /**
     * Retrieves or initializes the stack for the current coroutine context.
     *
     * If no stack exists in the current coroutine context, a new Stack instance
     * is created and stored for subsequent use.
     *
     * @return Stack The stack instance for the current coroutine context.
     */
    private function stack(): Stack
    {
        $ctx = Coroutine::getContext();

        if (!isset($ctx[self::KEY])) {
            $ctx[self::KEY] = new Stack();
        }

        return $ctx[self::KEY];
    }

    /**
     * Pushes a frame onto the stack in the current coroutine context.
     *
     * @param Frame $frame The frame to add to the stack.
     *
     * @return void
     */
    public function push(Frame $frame): void
    {
        $this->stack()->push($frame);
    }

    /**
     * Removes and returns the frame from the top of the stack in the current coroutine context.
     *
     * @return Frame The frame that was removed from the stack.
     *
     * @throws \RuntimeException If the stack is empty.
     */
    public function pop(): Frame
    {
        return $this->stack()->pop();
    }

    /**
     * Retrieves the frame at the top of the stack in the current coroutine context without removing it.
     *
     * @return Frame|null The current frame, or null if the stack is empty.
     */
    public function current(): ?Frame
    {
        return $this->stack()->current();
    }

    /**
     * Retrieves a frame at a specific index in the stack of the current coroutine context.
     *
     * @param int $index The index of the frame to retrieve.
     *
     * @return Frame|null The frame at the specified index, or null if not found.
     */
    public function getFrame(int $index): ?Frame
    {
        return $this->stack()->getFrame($index);
    }

    /**
     * Gets the number of frames currently in the stack of the current coroutine context.
     *
     * @return int The stack depth.
     */
    public function depth(): int
    {
        return $this->stack()->depth();
    }


    public function parent(): ?Frame
    {
        return $this->stack()->parent();
    }

    public function main(): ?Frame
    {
        return $this->stack()->main();
    }
}
