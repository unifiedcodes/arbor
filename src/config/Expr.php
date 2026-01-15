<?php

namespace Arbor\config;

use Arbor\config\expressions\CallExpression;
use Arbor\config\expressions\TypeCastExpression;
use Closure;

class Expr
{
    public static function on($value, Closure|string $callback)
    {
        return new CallExpression($value, $callback);
    }

    public static function cast($value, string $type)
    {
        return new TypeCastExpression($value, $type);
    }
}
