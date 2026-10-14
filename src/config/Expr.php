<?php

namespace Arbor\config;

use Arbor\config\expressions\ExpressionPipeline;

// static facade for expression pipeline.
final class Expr
{
    public static function __callStatic(
        string $name,
        array $arguments
    ): ExpressionPipeline {
        return (new ExpressionPipeline(null))
            ->{$name}(...$arguments);
    }
}
