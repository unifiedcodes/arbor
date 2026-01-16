<?php

namespace Arbor\config\expressions;

use Arbor\config\ResolverContext;
use InvalidArgumentException;
use RuntimeException;

/**
 * Expression that casts values to specific types.
 * 
 * Supports type casting to:
 * - string/str: Convert to string
 * - int/integer: Convert to integer
 * - float/double: Convert to float
 * - bool/boolean: Convert to boolean
 * - array: Convert to array
 * - json: Parse JSON string or encode/decode value
 * 
 * The inner value is resolved first to support nested expressions.
 */
final class TypeCastExpression implements ExpressionInterface
{
    /**
     * @param mixed $value The value to be cast (can be another expression)
     * @param string $type The target type for casting
     */
    public function __construct(
        private readonly mixed $value,
        private readonly string $type,
    ) {}

    /**
     * Resolves and casts the value using the provided context.
     * 
     * @param ResolverContext $ctx The resolver context for resolving nested expressions
     * @return mixed The resolved and cast value
     * @throws InvalidArgumentException If the cast type is unknown
     * @throws RuntimeException If JSON casting fails
     */
    public function resolve(ResolverContext $ctx): mixed
    {
        // First resolve inner value (important for nested DSL/Expr)
        $resolved = $ctx->resolve($this->value);

        return $this->castValue($resolved);
    }

    /**
     * Casts a value to the specified type.
     * 
     * @param mixed $value The value to cast
     * @return mixed The cast value
     * @throws InvalidArgumentException If the cast type is unknown
     * @throws RuntimeException If JSON casting fails
     */
    private function castValue(mixed $value): mixed
    {
        return match ($this->type) {
            'string', 'str'  => (string) $value,
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'array'          => (array) $value,
            'json'           => $this->castJson($value),

            default => throw new InvalidArgumentException(
                "Unknown cast type '{$this->type}'"
            )
        };
    }

    /**
     * Casts a value to/from JSON.
     * 
     * - If the value is a string, it's decoded as JSON
     * - Otherwise, the value is JSON-encoded then decoded to normalize it
     * 
     * @param mixed $value The value to cast as JSON
     * @return mixed The decoded JSON value (typically an array or scalar)
     * @throws RuntimeException If JSON decoding fails
     */
    private function castJson(mixed $value): mixed
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException("JSON cast failed: " . json_last_error_msg());
            }
            return $decoded;
        }

        // Convert non-strings to JSON if needed
        return json_decode(json_encode($value), true);
    }
}