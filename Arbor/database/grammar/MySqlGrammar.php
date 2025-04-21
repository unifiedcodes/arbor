<?php

namespace Arbor\database;


use Arbor\database\Grammar;


class MySQLGrammar extends Grammar
{
    /**
     * MySQL specific Grammar overrides
     */
    protected $parameterFormat = '?';
    protected $openQuote = '`';
    protected $closeQuote = '`';
}
