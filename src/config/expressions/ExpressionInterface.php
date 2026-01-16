<?php

namespace Arbor\config\expressions;

use Arbor\config\ResolverContext;

/**
 * Interface for configuration expression resolvers.
 * 
 * Expressions represent values that need to be resolved using a context,
 * such as references to other configuration values, environment variables,
 * or computed values.
 */
interface ExpressionInterface
{
    /**
     * Resolves the expression using the provided context.
     * 
     * @param ResolverContext $ctx The resolver context containing configuration data and resolution logic
     * @return mixed The resolved value (type depends on the specific expression implementation)
     */
    public function resolve(ResolverContext $ctx): mixed;
}