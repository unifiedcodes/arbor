<?php

namespace Arbor\scope;


/**
 * Stack implementation for managing scope frames.
 *
 * This class implements a Last-In-First-Out (LIFO) stack data structure
 * to maintain a collection of scope frames.
 */
final class Stack implements StackInterface
{
    /**
     * Array of frames stored in the stack.
     *
     * @var array<int, Frame>
     */
    private array $frames = [];

    /**
     * Pushes a frame onto the top of the stack.
     *
     * @param Frame $frame The frame to add to the stack.
     *
     * @return void
     */
    public function push(Frame $frame): void
    {
        $this->frames[] = $frame;
    }

    /**
     * Removes and returns the frame from the top of the stack.
     *
     * @return Frame The frame that was removed from the stack.
     *
     * @throws \RuntimeException If the stack is empty.
     */
    public function pop(): Frame
    {
        if (empty($this->frames)) {
            throw new \RuntimeException('Scope stack underflow');
        }

        return array_pop($this->frames);
    }

    /**
     * Retrieves the frame at the top of the stack without removing it.
     *
     * @return Frame|null The current frame, or null if the stack is empty.
     */
    public function current(): ?Frame
    {
        return $this->frames[array_key_last($this->frames)] ?? null;
    }

    /**
     * Retrieves a frame at a specific index in the stack.
     *
     * @param int $index The index of the frame to retrieve.
     *
     * @return Frame|null The frame at the specified index, or null if not found.
     */
    public function getFrame(int $index): ?Frame
    {
        return $this->frames[$index] ?? null;
    }

    /**
     * Gets the number of frames currently in the stack.
     *
     * @return int The stack depth.
     */
    public function depth(): int
    {
        return count($this->frames);
    }


    public function parent(): ?Frame
    {
        $depth = count($this->frames);

        if ($depth < 2) {
            return null;
        }

        return $this->frames[$depth - 2];
    }


    public function main(): ?Frame
    {
        return $this->frames[0] ?? null;
    }
}
