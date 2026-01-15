<?php

namespace Arbor\config\expressions;

use Arbor\config\expressions\ExpressionInterface;
use Arbor\config\ResolverContext;
use Closure;


final class CallExpression implements ExpressionInterface
{
    public function __construct(
        protected readonly string $value,
        protected readonly Closure $callback
    ) {}


    public function resolve(ResolverContext $ctx): mixed
    {
        $value = $ctx->resolve($this->value);

        return $this->callback
            ? ($this->callback)($value)
            : $value;
    }
}
