<?php

namespace Arbor\database\orm;

use BadMethodCallException;
use Arbor\database\Database;
use Arbor\database\QueryBuilder;


class ModelQuery
{
    protected QueryBuilder $builder;
    protected $modelClass;
    protected $database;


    public function __construct(Database $database, string $modelClass)
    {
        $this->database = $database;
        $this->modelClass = $modelClass;
        $this->builder = $database->table($modelClass::getTableName());
    }


    protected function hydrate(array $attributes)
    {
        $class = $this->modelClass;
        return new $class($this->database, $attributes, true);
    }


    public function __call(string $method, array $arguments)
    {
        if (!method_exists($this->builder, $method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        $result = $this->builder->$method(...$arguments);

        // If the builder returns itself (for chaining), we return $this
        return $result === $this->builder ? $this : $result;
    }

    // override result returning methods of $builder class.
    // add a magic call method with mixins, keep chain alive to Model when method returns $builder instance.
    // add a hydrate to $modelClass method.
}
