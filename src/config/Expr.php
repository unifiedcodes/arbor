<?php

namespace Arbor\config;

use Arbor\config\expressions\CallExpression;
use Arbor\config\expressions\TypeCastExpression;
use Closure;
use InvalidArgumentException;

/**
 * Expression builder for creating configuration expressions.
 * 
 * Provides static factory methods for creating various types of expressions
 * used in configuration processing, including callback expressions and type casts.
 */
class Expr
{
    /**
     * Creates a CallExpression that applies a callback to a value.
     * 
     * Supports three callback formats:
     * - A string (function name)
     * - A Closure
     * - An array with [callback, args] where callback is a string or Closure
     * 
     * @param mixed $value The value to pass to the callback
     * @param Closure|string|array $callback The callback to apply. Can be:
     *                                        - string: function name
     *                                        - Closure: anonymous function
     *                                        - array: [callback, args] tuple
     * @return CallExpression The created call expression
     * @throws InvalidArgumentException If callback array format is invalid
     */
    public static function on($value, Closure|string|array $callback): CallExpression
    {
        if (is_string($callback) || $callback instanceof Closure) {
            return new CallExpression($value, $callback);
        }

        if (!is_array($callback) && count($callback) !== 2) {
            throw new InvalidArgumentException(
                "Callback array must have exactly 2 elements: [callback, args]"
            );
        }

        [$cb, $args] = $callback;


        if (!$cb instanceof Closure && !is_string($cb)) {
            throw new InvalidArgumentException(
                "First element of callback array must be a Closure or string"
            );
        }

        if (!is_array($args)) {
            throw new InvalidArgumentException(
                "Second element of callback array must be an array of args"
            );
        }

        return new CallExpression($value, $cb, $args);
    }

    /**
     * Creates a TypeCastExpression that casts a value to a specific type.
     * 
     * @param mixed $value The value to be type cast
     * @param string $type The target type for the cast (e.g., 'int', 'string', 'bool', 'float', 'array', 'object')
     * @return TypeCastExpression The created type cast expression
     */
    public static function cast($value, string $type): TypeCastExpression
    {
        return new TypeCastExpression($value, $type);
    }
}