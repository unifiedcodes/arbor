<?php

namespace Arbor\http;

use Arbor\http\context\RequestContext;

/**
 * Interface for managing a stack of request contexts.
 * 
 * This interface defines operations for a stack data structure that holds
 * RequestContext objects, allowing for nested request handling and context
 * management throughout the request lifecycle.
 */
interface RequestStackWRInterface
{
    /**
     * Pushes a new request context onto the top of the stack.
     * 
     * @param RequestContext $context The request context to add to the stack
     * @return void
     */
    public function push(RequestContext $context): void;

    /**
     * Removes and returns the request context from the top of the stack.
     * 
     * @return RequestContext|null The removed context, or null if the stack is empty
     */
    public function pop(): ?RequestContext;

    /**
     * Removes all request contexts from the stack.
     * 
     * @return void
     */
    public function clear(): void;

    /**
     * Returns a request context from the stack without removing it.
     * 
     * @param int $depth The depth to peek into the stack (1 = top, 2 = second from top, etc.)
     * @return RequestContext|null The context at the specified depth, or null if not found
     */
    public function peek(int $depth = 1): ?RequestContext;
}
