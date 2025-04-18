<?php

namespace Arbor\contracts\http;

use Arbor\http\context\RequestContext;

/**
 * Interface for reading the request stack.
 * 
 * This interface provides methods for interacting with the current request stack,
 * offering access to the current, parent, and main request contexts. It also 
 * provides functionality for retrieving a summary of all request contexts in the stack.
 * 
 * Key Features:
 * - Access to the current, parent, and main request contexts.
 * - Checking if the stack contains any request contexts.
 * - Retrieving the depth of the request stack.
 * - Converting the stack to a summary array.
 * 
 * @package Arbor\contracts
 */
interface RequestStackRO
{
    /**
     * Get the current RequestContext in the stack.
     *
     * @return RequestContext|null The current RequestContext, or null if no contexts exist.
     */
    public function getCurrent(): ?RequestContext;

    /**
     * Get the parent RequestContext from the stack.
     *
     * @return RequestContext|null The parent RequestContext, or null if there is no parent.
     */
    public function getParent(): ?RequestContext;

    /**
     * Get the main (first) RequestContext in the stack.
     *
     * @return RequestContext|null The main RequestContext, or null if no contexts exist.
     */
    public function getMain(): ?RequestContext;

    /**
     * Check if there are any RequestContext objects in the stack.
     *
     * @return bool True if the stack contains request contexts, false otherwise.
     */
    public function hasRequestContexts(): bool;

    /**
     * Get the current depth of the request stack.
     *
     * The depth indicates the number of RequestContext objects in the stack.
     *
     * @return int The depth of the request stack.
     */
    public function getDepth(): int;

    /**
     * Get an array summary of all RequestContext objects in the stack.
     *
     * The summary provides a serialized version of the context, ordered from
     * the most recent (top) to the oldest (bottom) context in the stack.
     * 
     * @return array<int, array|null> An array of request context summaries.
     */
    public function toArraySummary(): array;
}
