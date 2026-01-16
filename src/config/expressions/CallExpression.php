<?php

namespace Arbor\config\expressions;

use Arbor\config\expressions\ExpressionInterface;
use Arbor\config\ResolverContext;
use Closure;
use RuntimeException;

/**
 * Represents a callable expression that applies a callback function to a resolved value.
 * 
 * This expression resolves a value and then applies a callback to it, which can be:
 * - A custom closure
 * - A static macro registered in the context
 * - An instance macro registered in the context
 * - A global function registered in the context
 * 
 * @package Arbor\config\expressions
 */
final class CallExpression implements ExpressionInterface
{
    /**
     * @param string $value The value expression to resolve before applying the callback
     * @param Closure|string $callback The callback to apply (closure or callback name)
     * @param array<int, mixed> $args Additional arguments to pass to the callback
     */
    public function __construct(
        protected readonly string $value,
        protected readonly Closure|string $callback,
        protected readonly array $args = []
    ) {}

    /**
     * Resolves the expression by applying the callback to the resolved value.
     * 
     * The callback resolution follows this priority order:
     * 1. Custom closure (if callback is a Closure)
     * 2. Static macro (registered in context)
     * 3. Instance macro (registered in context)
     * 4. Global function (registered in context)
     * 
     * @param ResolverContext $ctx The resolver context for resolving values and callbacks
     * @return mixed The result of applying the callback to the resolved value
     * @throws RuntimeException If the callback cannot be resolved through any method
     */
    public function resolve(ResolverContext $ctx): mixed
    {
        $value = $ctx->resolve($this->value);

        // custom resolver
        if ($this->callback instanceof Closure) {
            return ($this->callback)($value, ...$this->args);
        }

        // static macro
        if ($ctx->hasStatic($this->callback)) { 
            $callable = $ctx->getStatic($this->callback);
            return $callable($value, ...$this->args);
        }

        // instance macro
        if ($ctx->hasInstance($this->callback)) {
            $callable = $ctx->getInstance($this->callback);
            return $callable($value, ...$this->args);
        }

        // global function
        if ($ctx->hasGlobal($this->callback)) {
            $callable = $ctx->getGlobal($this->callback);
            return $callable($value, ...$this->args);
        }

        throw new RuntimeException("Call '{$this->callback}' could not be resolved");
    }
}