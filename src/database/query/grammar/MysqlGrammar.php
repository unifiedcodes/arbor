<?php

namespace Arbor\database\query\grammar;

use Arbor\database\query\grammar\Grammar;

/**
 * MySQL-specific SQL grammar implementation.
 *
 * This class extends the base Grammar class to provide MySQL-specific
 * SQL syntax handling, such as proper identifier quoting and parameter placeholders.
 */
class MysqlGrammar extends Grammar
{
    /**
     * Wraps an identifier in MySQL-specific backtick quotes.
     *
     * @param string $input The identifier to wrap
     * @return string The wrapped identifier
     */
    protected static function wrap(string $input): string
    {
        return '`' . $input . '`';
    }

    /**
     * Returns the MySQL parameter placeholder character.
     *
     * @return string The parameter placeholder ('?')
     */
    protected static function placeholder(): string
    {
        return '?';
    }
}
