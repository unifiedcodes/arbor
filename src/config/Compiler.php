<?php

namespace Arbor\config;

use Arbor\config\expressions\ExpressionInterface;
use Arbor\config\expressions\DSLExpression;
use Arbor\config\ResolverContext;

/**
 * Compiles configuration data by resolving DSL expressions and processing nested structures.
 * 
 * This compiler processes configuration arrays, detects DSL expressions in strings,
 * and recursively resolves all expressions using the provided registry.
 * @package Arbor\config
 */
final class Compiler
{
    /**
     * @param Registry $registry The registry used for resolving configuration values
     */
    public function __construct(
        protected Registry $registry
    ) {}

    /**
     * Compiles configuration data by resolving all DSL expressions and nested values.
     * 
     * @param array<mixed> $data The configuration data to compile
     * @return mixed The compiled configuration with all expressions resolved
     */
    public function compile(mixed $data): mixed
    {
        $ctx = new ResolverContext(
            registry: $this->registry,
        );

        $ctx->setResolver(fn(mixed $v): mixed => $this->compileInternal($v, $ctx));

        return $this->compileInternal($data, $ctx);
    }

    /**
     * Internal recursive compilation method that processes values and resolves expressions.
     * 
     * Strings containing DSL syntax are converted to DSLExpression objects.
     * Expression objects are resolved using the context.
     * Arrays are recursively processed for nested values.
     * 
     * @param mixed $value The value to compile
     * @param ResolverContext $ctx The resolver context containing registry and resolver function
     * @return mixed The compiled value with expressions resolved
     */
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

    /**
     * Checks if a string contains DSL syntax markers.
     * 
     * A string is considered to contain DSL if it has both opening '{' and closing '}' braces.
     * 
     * @param string $value The string to check for DSL syntax
     * @return bool True if the string contains DSL markers, false otherwise
     */
    private function containsDsl(string $value): bool
    {
        return str_contains($value, '{') && str_contains($value, '}');
    }
}
