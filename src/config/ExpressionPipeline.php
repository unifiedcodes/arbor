<?php

namespace Arbor\config;

use Arbor\config\expressions\CallExpression;
use Arbor\config\ResolverContext;
use Arbor\config\expressions\ExpressionInterface;
use Arbor\config\expressions\TypeCastExpression;
use Closure;

/**
 * Expression pipeline for chaining and resolving configuration expressions.
 *
 * This class implements a fluent interface for building a pipeline of expressions
 * that are resolved sequentially. It supports type casting and callback invocations
 * through a stack-based evaluation system.
 * @package Arbor\config
 */
final class ExpressionPipeline implements ExpressionInterface
{
    /**
     * Registry of expression resolvers mapped by class name.
     *
     * @var array<string, Closure|null>
     */
    protected array $resolvers = [];

    /**
     * Stack of expressions to be evaluated in order.
     *
     * Each entry contains the expression class and constructor arguments.
     *
     * @var array<int, array{class: string, args: array}>
     */
    protected array $expressionStack = [];

    /**
     * Create a new expression pipeline.
     *
     * @param string $value The initial value to be resolved and processed
     */
    public function __construct(protected readonly string $value)
    {
        $this->resolvers = [
            CallExpression::class     => fn($value, $args) => new CallExpression($value, ...$args),
            TypeCastExpression::class => fn($value, $args) => new TypeCastExpression($value, ...$args),
        ];
    }

    /**
     * Resolve the expression pipeline.
     *
     * First resolves the initial value, then applies all queued expressions
     * in the order they were added to the pipeline.
     *
     * @param ResolverContext $ctx The resolver context containing configuration data
     * @return mixed The final resolved value after all transformations
     */
    public function resolve(ResolverContext $ctx): mixed
    {
        // First resolve inner value (important for nested DSL/Expr)
        $resolved = $ctx->resolve($this->value);

        return $this->pipeline($resolved, $ctx);
    }

    /**
     * Execute the expression pipeline on a resolved value.
     *
     * Iterates through all registered resolvers and applies matching expressions
     * from the expression stack in order.
     *
     * @param mixed $value The value to process through the pipeline
     * @param ResolverContext $ctx The resolver context for nested resolutions
     * @return mixed The transformed value after all expressions are applied
     */
    protected function pipeline(mixed $value, ResolverContext $ctx): mixed
    {
        foreach ($this->resolvers as $class => $factory) {

            foreach ($this->expressionStack as $expr) {
                if ($expr['class'] !== $class) {
                    continue;
                }

                // Build expression via resolver factory
                $expression = $factory
                    ? $factory($value, $expr['args'])
                    : new $class($value);

                if ($expression instanceof ExpressionInterface) {
                    $value = $expression->resolve($ctx);
                }
            }
        }

        return $value;
    }

    /**
     * Add a type cast expression to the pipeline.
     *
     * @param string $type The target type for casting (e.g., 'int', 'string', 'bool')
     * @return self Returns this instance for method chaining
     */
    public function cast(string $type): self
    {
        $this->expressionStack[] = [
            'class' => TypeCastExpression::class,
            'args' => [$type],
        ];
        return $this;
    }

    /**
     * Add a callback invocation expression to the pipeline.
     *
     * @param Closure|string|array $callback The callback to invoke on the value
     * @param array $args Additional arguments to pass to the callback
     * @return self Returns this instance for method chaining
     */
    public function call(Closure|string|array $callback, array $args = []): self
    {
        $this->expressionStack[] = [
            'class' => CallExpression::class,
            'args' => [$callback, $args],
        ];
        return $this;
    }
}
