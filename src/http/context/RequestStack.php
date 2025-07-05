<?php

namespace Arbor\http\context;

use Arbor\contracts\http\RequestStackWR;
use Arbor\contracts\http\RequestStackRO;
use Arbor\http\Request;


/**
 * RequestStack manages a stack of RequestContext objects.
 * 
 * This class provides functionality similar to Symfony's RequestStack,
 * allowing for nested request handling while maintaining the proper context.
 * It enables a hierarchical approach to request handling where sub-requests
 * can be processed while maintaining access to the parent request context.
 * 
 * @package Arbor\http\context
 */
class RequestStack implements RequestStackRO, RequestStackWR
{
    /**
     * The stack of request contexts.
     * 
     * @var RequestContext[]
     */
    private array $stack = [];

    /**
     * Push a RequestContext onto the stack.
     *
     * @param RequestContext $context The request context to add to the stack
     * @return void
     */
    public function push(RequestContext $context): void
    {
        $this->stack[] = $context;
    }

    /**
     * Pop the current RequestContext from the stack.
     *
     * @return RequestContext|null The RequestContext that was popped or null if stack is empty
     */
    public function pop(): ?RequestContext
    {
        if (empty($this->stack)) {
            return null;
        }

        return array_pop($this->stack);
    }

    /**
     * Get the current RequestContext.
     *
     * @return RequestContext|null The current RequestContext or null if stack is empty
     */
    public function getCurrent(): ?RequestContext
    {
        if (empty($this->stack)) {
            return null;
        }

        return $this->stack[count($this->stack) - 1];
    }

    /**
     * Get the parent RequestContext.
     *
     * @return RequestContext|null The parent RequestContext or null if there is no parent
     */
    public function getParent(): ?RequestContext
    {
        if (count($this->stack) < 2) {
            return null;
        }

        return $this->stack[count($this->stack) - 2];
    }

    /**
     * Get the main RequestContext (the one at the bottom of the stack).
     *
     * @return RequestContext|null The main RequestContext or null if stack is empty
     */
    public function getMain(): ?RequestContext
    {
        return $this->stack[0] ?? null;
    }

    /**
     * Get all RequestContext objects in the stack.
     *
     * @return RequestContext[] The array of RequestContext objects
     */
    public function getStack(): array
    {
        return $this->stack;
    }

    /**
     * Check if the stack contains any RequestContext objects.
     *
     * @return bool True if stack is not empty, false otherwise
     */
    public function hasRequestContexts(): bool
    {
        return !empty($this->stack);
    }

    /**
     * Get the current stack size.
     *
     * @return int The number of RequestContext objects in the stack
     */
    public function getDepth(): int
    {
        return count($this->stack);
    }

    /**
     * Clear the entire stack.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->stack = [];
    }

    /**
     * Peek at a RequestContext at a specific depth in the stack without removing it.
     * 
     * A depth of 1 returns the current context (same as getCurrent()),
     * a depth of 2 returns the parent (same as getParent()), and so on.
     *
     * @param int $depth The depth to peek at (1-based, where 1 is the top of the stack)
     * @return RequestContext|null The RequestContext at the specified depth or null if not found
     */
    public function peek(int $depth = 1): ?RequestContext
    {
        if ($depth < 1) {
            return null;
        }

        $index = count($this->stack) - $depth;

        if ($index < 0 || $index >= count($this->stack)) {
            return null;
        }

        return $this->stack[$index];
    }

    /**
     * Convert the stack to an array of summary information.
     * 
     * Creates an array of context summaries, ordered from most recent (top of stack)
     * to oldest (bottom of stack).
     *
     * @return array<int, array|null> Array of context summaries
     */
    public function toArraySummary(): array
    {
        return array_map(
            function (RequestContext $context): ?array {
                return $context->getSummary();
            },
            array_reverse($this->stack)
        );
    }


    /**
     * Check if a request has already been dispatched in the stack.
     *
     * Helps prevent circular sub-request loops by checking for URI + Method match.
     *
     * @param Request $request
     * @return bool
     */
    public function alreadyDispatched(Request $request): bool
    {

        $targetSignature = $this->normalizedRequestString($request);
        $stackSize = count($this->stack);

        // Skip the current request (top of the stack) in the comparison
        // Only check previous requests in the stack
        for ($i = 0; $i < $stackSize - 1; $i++) {
            $existing = $this->stack[$i]->getRequest();
            $existingSignature = $this->normalizedRequestString($existing);

            if ($existingSignature === $targetSignature) {
                return true;
            }
        }

        return false;
    }


    protected function normalizedRequestString(Request $request): string
    {
        // Get the full URI including query parameters
        $uri = (string) $request->getUri();

        // Normalize slashes consistently
        $uri = rtrim($uri, '/');
        $uri = $uri === '' ? '/' : $uri;

        $method = strtoupper($request->getMethod());

        // Include relevant request characteristics
        $signature = $method . ' ' . $uri;

        return $signature;
    }
}
