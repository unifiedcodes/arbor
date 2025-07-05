<?php

namespace Arbor\database\utility;

use Exception;

/**
 * Class GrammarResolver
 * 
 * Resolves database grammar classes based on driver names.
 * Converts driver names to appropriate class names and instantiates them.
 * 
 * @package Arbor\database\utility
 */
class GrammarResolver
{
    /**
     * The base namespace for grammar classes.
     * 
     * @var string
     */
    protected string $namespace;

    /**
     * Constructor.
     * 
     * @param string $namespace The base namespace for grammar classes
     */
    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * Resolves a grammar class based on the provided driver name.
     * 
     * @param string $driver The database driver name
     * @return object The instantiated grammar class
     * @throws Exception If the grammar class does not exist
     */
    public function resolve(string $driver): object
    {
        // Transform driver to PascalCase Grammar class name
        $className = $this->namespace . $this->studlyCase($driver) . 'Grammar';

        if (!class_exists($className)) {
            throw new Exception("Grammar class '{$className}' not found for driver '{$driver}'.");
        }

        return new $className();
    }

    /**
     * Converts a string to StudlyCase (PascalCase).
     * 
     * @param string $value The string to convert
     * @return string The StudlyCase version of the string
     */
    protected function studlyCase(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }
}
