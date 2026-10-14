<?php

namespace Arbor\config\expressions;

use Arbor\config\ResolverContext;

final class ConditionalExpression implements ExpressionInterface
{
    public function __construct(
        private readonly mixed $condition,
        private readonly mixed $then,
        private readonly mixed $else,
    ) {}

    public function resolve(ResolverContext $ctx): mixed
    {
        $condition = $ctx->resolve($this->condition);

        return $ctx->resolve(
            $condition ? $this->then : $this->else
        );
    }
}
