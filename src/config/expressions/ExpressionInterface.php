<?php

namespace Arbor\config\expressions;

use Arbor\config\ResolverContext;


interface ExpressionInterface
{
    public function resolve(ResolverContext $ctx): mixed;
}
