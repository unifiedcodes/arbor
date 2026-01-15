<?php

namespace Arbor\config\expressions;

use Arbor\config\expressions\ExpressionInterface;
use Arbor\config\ResolverContext;
use Throwable;


final class DSLExpression implements ExpressionInterface
{
    public function __construct(
        private readonly string $raw
    ) {}

    public function resolve(ResolverContext $ctx): mixed
    {
        return $this->interpolateDSL($this->raw, $ctx);
    }

    private function interpolateDSL(string $value, ResolverContext $ctx): string
    {
        return preg_replace_callback('/\{([^}]+)\}/', function ($matches) use ($ctx) {
            return $this->resolveReferenceBlock($matches[1], $ctx);
        }, $value);
    }

    private function resolveReferenceBlock(string $block, ResolverContext $ctx): mixed
    {
        $parts = explode('|', $block);

        // last part = default literal
        $default = array_pop($parts);

        foreach ($parts as $key) {

            // recursive resolution via context
            $value = $this->resolveKey($key, $ctx);

            if ($value !== '__not_found_value__') {
                return $value;
            }
        }

        return $default;
    }

    private function resolveKey(string $key, ResolverContext $ctx): mixed
    {
        try {
            // ask registry for raw value
            $raw = $ctx->ref($key, null);
        } catch (Throwable $th) {
            $raw = '__not_found_value__';
        }

        // then resolve recursively through compiler
        return $ctx->resolve($raw);
    }
}
