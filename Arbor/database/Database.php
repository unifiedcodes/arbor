<?php

namespace Arbor\database;


use Arbor\database\connection\Connection;


// Facade for database operations.

class Database
{

    public function table(string $tableName)
    {
        // create an instance of query builder.
        // adds table name in it.
        // returns query builder
    }

    public function query()
    {
        // helper method to do queries
    }

    public function values()
    {
        // helper method to do values
    }

    public function execute()
    {
        // helper method to execute.
    }

    public function fetch()
    {
        // helper method to execute and fetch.
        // can be extended via fetchOne() and fetchAll() for convinience.
    }
}
