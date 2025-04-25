<?php

namespace Arbor\database\query\grammar;


use Arbor\database\query\grammar\Grammar;


class MySQLGrammar extends Grammar
{
    /**
     * MySQL specific Grammar overrides
     */
    protected $parameterFormat = '?';
    protected $openQuote = '`';
    protected $closeQuote = '`';
}
