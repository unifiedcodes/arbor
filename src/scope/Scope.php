<?php

namespace Arbor\scope;

use RuntimeException;


/**
 * Manages scope frames using a stack-based approach.
 *
 * This class provides functionality to create and manage scoped contexts,
 * allowing for the storage and retrieval of variables within nested scopes.
 */
final class Scope
{
    /**
     * Constructor.
     *
     * @param StackInterface $stack The stack instance used to manage scope frames.
     */
    public function __construct(
        private StackInterface $stack
    ) {}

    /**
     * Enters a new scope by pushing a new frame onto the stack.
     *
     * @return void
     */
    public function enter(): void
    {
        $this->stack->push(new Frame());
    }

    /**
     * Leaves the current scope by popping the current frame from the stack.
     *
     * @return void
     */
    public function leave(): void
    {
        $this->stack->pop();
    }

    /**
     * Sets a variable in the current scope frame.
     *
     * @param string $key   The variable key/name.
     * @param mixed  $value The value to store.
     *
     * @return void
     *
     * @throws RuntimeException If no active scope frame exists.
     */
    public function set(string $key, mixed $value): void
    {
        $frame = $this->stack->current();

        if (!$frame) {
            throw new RuntimeException('No active scope frame');
        }

        $frame->set($key, $value);
    }

    /**
     * Checks if a variable exists in the current scope frame.
     *
     * @param string $key The variable key/name to check.
     *
     * @return bool True if the variable exists in the current frame, false otherwise.
     */
    public function has(string $key): bool
    {
        $frame = $this->stack->current();
        return $frame ? $frame->has($key) : false;
    }

    /**
     * Retrieves a variable from the current scope frame.
     *
     * @param string $key The variable key/name to retrieve.
     *
     * @return mixed The variable value, or null if the frame doesn't exist or the key is not found.
     */
    public function get(string $key): mixed
    {
        $frame = $this->stack->current();
        return $frame ? $frame->get($key) : null;
    }

    /**
     * Retrieves a specific frame from the stack by index.
     *
     * @param int $index The frame index.
     *
     * @return Frame|null The frame at the specified index, or null if not found.
     */
    public function getFrame(int $index): ?Frame
    {
        return $this->stack->getFrame($index);
    }

    /**
     * Gets the current depth of the scope stack.
     *
     * @return int The number of frames currently on the stack.
     */
    public function depth(): int
    {
        return $this->stack->depth();
    }


    public function parent(): ?Frame
    {
        return $this->stack->parent();
    }


    public function main(): ?Frame
    {
        return $this->stack->main();
    }
}
