<?php

namespace Arbor\scope;


/**
 * Interface for managing a stack of scope frames.
 *
 * Defines the contract for a Last-In-First-Out (LIFO) stack implementation
 * that manages scope frames in a nested scope system.
 */
interface StackInterface
{
    /**
     * Pushes a frame onto the top of the stack.
     *
     * @param Frame $frame The frame to add to the stack.
     *
     * @return void
     */
    public function push(Frame $frame): void;

    /**
     * Removes and returns the frame from the top of the stack.
     *
     * @return Frame The frame that was removed from the stack.
     *
     */
    public function pop(): Frame;

    /**
     * Retrieves the frame at the top of the stack without removing it.
     *
     * @return Frame|null The current frame, or null if the stack is empty.
     */
    public function current(): ?Frame;

    /**
     * Retrieves a frame at a specific index in the stack.
     *
     * @param int $index The index of the frame to retrieve.
     *
     * @return Frame|null The frame at the specified index, or null if not found.
     */
    public function getFrame(int $index): ?Frame;

    /**
     * Gets the number of frames currently in the stack.
     *
     * @return int The stack depth.
     */
    public function depth(): int;

    /**
     * Retrieves the main (root) frame of the stack.
     *
     * Returns the frame at the bottom of the stack, representing the outermost
     * or global scope level. This is typically the first frame that was pushed
     * onto the stack and should never be removed during normal operation.
     *
     * @return Frame The root frame of the stack.
     *
     */
    public function main(): ?Frame;

    /**
     * Retrieves the parent frame of the current scope.
     *
     * Returns the frame immediately below the current frame in the stack.
     * This represents the enclosing scope for the current frame.
     *
     * @return Frame The parent frame of the current scope.
     */
    public function parent(): ?Frame;
}
