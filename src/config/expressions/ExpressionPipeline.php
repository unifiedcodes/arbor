<?php

namespace Arbor\config\expressions;

use Arbor\config\expressions\CallExpression;
use Arbor\config\expressions\ConditionalExpression;
use Arbor\config\expressions\ExpressionInterface;
use Arbor\config\expressions\TypeCastExpression;
use Arbor\Config\ResolverContext;

use Closure;

/**
 * Expression pipeline for chaining and resolving configuration expressions.
 *
 * @package Arbor\config
 */
final class ExpressionPipeline implements ExpressionInterface
{
    /**
     * Callbacks executed sequentially on the current value.
     *
     * @var list<Closure>
     */
    protected array $steps = [];

    public function __construct(
        protected readonly mixed $value
    ) {}


    public static function val(mixed $value): self
    {
        return new self($value);
    }

    /**
     * Resolve the initial value and execute each pipeline step.
     */
    public function resolve(ResolverContext $ctx): mixed
    {
        $value = $ctx->resolve($this->value);

        foreach ($this->steps as $step) {
            $value = $step($value, $ctx);
        }

        return $value;
    }

    /**
     * Add a type-cast step.
     */
    public function cast(string $type): self
    {
        $this->steps[] = static fn(
            mixed $value,
            ResolverContext $ctx
        ): mixed => (new TypeCastExpression($value, $type))
            ->resolve($ctx);

        return $this;
    }

    /**
     * Add a callback invocation step.
     */
    public function call(
        Closure|string|array $callback,
        array $args = []
    ): self {
        $this->steps[] = static fn(
            mixed $value,
            ResolverContext $ctx
        ): mixed => (new CallExpression($value, $callback, $args))
            ->resolve($ctx);

        return $this;
    }

    /**
     * Add a conditional step.
     */
    public function when(
        mixed $condition,
        mixed $then,
        mixed $else
    ): self {
        $this->steps[] = static fn(
            mixed $value,
            ResolverContext $ctx
        ): mixed => (new ConditionalExpression(
            $condition,
            $then,
            $else
        ))->resolve($ctx);

        return $this;
    }
}
