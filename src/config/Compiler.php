<?php

namespace Arbor\config;


use Arbor\config\expressions\ExpressionInterface;
use Arbor\config\expressions\DSLExpression;
use Arbor\config\ResolverContext;


final class Compiler
{
    public function __construct(protected Registry $registry) {}

    public function compile(array $data): mixed
    {
        $ctx = new ResolverContext(
            registry: $this->registry,
        );

        $ctx->setResolver(fn(mixed $v) => $this->compileInternal($v, $ctx));

        return $this->compileInternal($data, $ctx);
    }


    private function compileInternal(mixed $value, ResolverContext $ctx): mixed
    {
        if (is_string($value) && $this->containsDsl($value)) {
            $value = new DSLExpression($value);
        }

        if ($value instanceof ExpressionInterface) {
            return $value->resolve($ctx);
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->compileInternal($v, $ctx);
            }
        }

        return $value;
    }


    function containsDsl(string $value): bool
    {
        return str_contains($value, '{') && str_contains($value, '}');
    }
}
