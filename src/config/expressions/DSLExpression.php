<?php

namespace Arbor\config\expressions;

use Arbor\config\expressions\ExpressionInterface;
use Arbor\config\ResolverContext;
use Throwable;

/**
 * DSL Expression resolver that supports interpolation and fallback values.
 * 
 * Resolves expressions in the format: {key1|key2|default}
 * - Tries each key in order until a value is found
 * - Returns the default value if no keys resolve
 * - Supports recursive resolution through the context
 * 
 * Example: {DATABASE_URL|DB_CONNECTION|mysql://localhost}
 */
final class DSLExpression implements ExpressionInterface
{
    /**
     * @param string $raw The raw DSL expression string to resolve
     */
    public function __construct(
        private readonly string $raw
    ) {}

    /**
     * Resolves the DSL expression using the provided context.
     * 
     * @param ResolverContext $ctx The resolver context for looking up references
     * @return mixed The resolved value (typically a string, but can be any type)
     */
    public function resolve(ResolverContext $ctx): mixed
    {
        return $this->interpolateDSL($this->raw, $ctx);
    }

    /**
     * Interpolates DSL expressions within curly braces {}.
     * 
     * @param string $value The string containing DSL expressions
     * @param ResolverContext $ctx The resolver context for looking up references
     * @return string The interpolated string with resolved values
     */
    private function interpolateDSL(string $value, ResolverContext $ctx): string
    {
        return preg_replace_callback('/\{([^}]+)\}/', function ($matches) use ($ctx) {
            return $this->resolveReferenceBlock($matches[1], $ctx);
        }, $value);
    }

    /**
     * Resolves a reference block with pipe-separated fallback values.
     * 
     * Format: key1|key2|key3|default
     * - Tries each key in order
     * - Last value is always used as the literal default
     * 
     * @param string $block The content inside curly braces (without the braces)
     * @param ResolverContext $ctx The resolver context for looking up references
     * @return mixed The first successfully resolved value or the default
     */
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

    /**
     * Resolves a single key through the context.
     * 
     * @param string $key The reference key to resolve
     * @param ResolverContext $ctx The resolver context for looking up references
     * @return mixed The resolved value or '__not_found_value__' sentinel if not found
     */
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
