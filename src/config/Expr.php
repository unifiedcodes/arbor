<?php

namespace Arbor\config;

use Exception;
use Arbor\config\expressions\CallExpression;
use Arbor\config\expressions\TypeCastExpression;


class Expr
{
    public static function on($value, $callback)
    {
        return new CallExpression($value, $callback);
    }

    public static function cast($value)
    {
        return new TypeCastExpression($value);
    }
}
