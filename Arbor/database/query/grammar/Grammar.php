<?php

namespace Arbor\database\query\grammar;

use Arbor\database\connection\Connection;
use Arbor\database\query\QueryBuilder;


abstract class Grammar
{
    public function compile($ast) {}
}
