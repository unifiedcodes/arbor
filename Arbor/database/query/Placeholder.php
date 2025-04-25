<?php

namespace Arbor\database\query;

/**
 * Placeholder enum for SQL bind-parameters.
 *
 * This pure (unit) enum represents a flyweight marker in the AST
 * indicating “?” placeholders. Each case is a singleton instance,
 * so there is zero allocation overhead when building queries.
 *
 * Usage:
 *   - In Builder:
 *       $position = Placeholder::void;  
 *
 * Requirements:
 *   - PHP 8.1 or later
 */
enum Placeholder
{
/** determine a void to be filled by grammaer as placeholder */
    case void;
}
