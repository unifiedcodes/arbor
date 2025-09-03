<?php

namespace Arbor\database\orm\relations;


use Arbor\database\orm\Model;
use Arbor\database\orm\ModelQuery;


abstract class Relationship
{
    protected Model $parent;
    protected ModelQuery $query;


    public function __construct(Model $parent, ModelQuery $query)
    {
        $this->parent = $parent;
        $this->query = $query;
    }


    abstract public function resolve(); // default resolution


    public function getQuery(): ModelQuery
    {
        return $this->query;
    }


    public function getParent(): Model
    {
        return $this->parent;
    }


    public function __call($method, $arguments)
    {
        if (method_exists($this->query, $method)) {
            $result = $this->query->$method(...$arguments);

            // If the query returns itself (chainable), return the Relationship for further chaining
            return $result instanceof \Arbor\database\orm\ModelQuery ? $this : $result;
        }

        throw new \BadMethodCallException(
            "Method {$method} does not exist on " . static::class
        );
    }
}
