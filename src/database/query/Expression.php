<?php

namespace Arbor\database\query;

/**
 * Class Expression
 * 
 * Represents a raw database expression that should be used as-is in SQL queries.
 * This class is used when values need to be inserted directly into queries
 * without being escaped or quoted.
 * 
 * @package Arbor\database\query
 */
class Expression
{
    /**
     * The raw expression value.
     * 
     * @var string
     */
    protected string $value;

    /**
     * Creates a new Expression instance.
     * 
     * @param string $value The raw expression value to be used in queries
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Retrieves the raw expression value.
     * 
     * @return string The raw expression value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Magic method to return value when casting
     * 
     * @return string The raw expression value
     * 
     */
    public function __toString(): string
    {
        return $this->getValue();
    }
}
