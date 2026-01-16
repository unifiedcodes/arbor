<?php

namespace Arbor\config;

use Arbor\config\expressions\CallExpression;
use Arbor\config\expressions\DSLExpression;
use Arbor\config\ResolverContext;
use Arbor\config\expressions\ExpressionInterface;
use Arbor\config\expressions\TypeCastExpression;
use Closure;


final class ExpressionPipeline implements ExpressionInterface
{
    protected array $resolvers = [];
    protected array $expressionStack = [];


    public function __construct(protected readonly string $value)
    {
        $this->resolvers = [
            CallExpression::class     => fn($value, $args) => new CallExpression($value, ...$args),
            TypeCastExpression::class => fn($value, $args) => new TypeCastExpression($value, ...$args),
        ];
    }


    public function resolve(ResolverContext $ctx): mixed
    {
        // First resolve inner value (important for nested DSL/Expr)
        $resolved = $ctx->resolve($this->value);

        return $this->pipeline($resolved, $ctx);
    }


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


    public function cast(string $type): self
    {
        $this->expressionStack[] = [
            'class' => TypeCastExpression::class,
            'args' => [$type],
        ];
        return $this;
    }

    public function call(Closure|string|array $callback, array $args): self
    {
        $this->expressionStack[] = [
            'class' => CallExpression::class,
            'args' => [$callback, $args],
        ];
        return $this;
    }
}
