<?php

namespace Arbor\validation;

use RuntimeException;
use InvalidArgumentException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Arbor\contracts\validation\RuleInterface;
use Arbor\contracts\validation\RuleListInterface;

/**
 * Registry class for managing validation rules
 * 
 * This class serves as a central registry for validation rules, allowing registration
 * of both individual rules (RuleInterface) and collections of rules (RuleListInterface).
 * It provides a unified interface for registering and resolving validation rules by name.
 * 
 * @package Arbor\validation
 * 
 */
class Registry
{
    /**
     * Storage for registered validation rules
     * 
     * Array structure: [rule_name => [object_instance, method_name]]
     * - rule_name: string identifier for the rule
     * - object_instance: the rule object or rule list object
     * - method_name: the method to call on the object for validation
     * 
     * @var array
     */
    protected $rules = [];

    /**
     * Register a validation rule or rule list
     * 
     * This method accepts either a single rule implementing RuleInterface
     * or a collection of rules implementing RuleListInterface. It dispatches
     * to the appropriate registration method based on the object type.
     * 
     * @param RuleInterface|RuleListInterface $class The rule or rule list to register
     * @return void
     * @throws InvalidArgumentException If the provided object doesn't implement required interfaces
     */
    public function register(RuleInterface|RuleListInterface $class)
    {
        // Handle single rule
        if ($class instanceof RuleInterface) {
            return $this->registerRule($class);
        }

        // Handle rule list
        if ($class instanceof RuleListInterface) {
            return $this->registerRuleList($class);
        }

        // Throw exception for invalid types
        throw new InvalidArgumentException(
            sprintf(
                'Invalid class type passed to Validator::register. Expected RuleInterface or RuleListInterface, got %s',
                get_class($class)
            )
        );
    }

    /**
     * Register validation rules from a directory
     * 
     * Automatically discovers and registers validation rules from PHP files within
     * a specified directory. This method recursively scans the directory, converts
     * file paths to fully qualified class names using the provided namespace, and
     * registers any classes that implement the required validation interfaces.
     * 
     * The method assumes:
     * - All PHP files contain classes with no-argument constructors
     * - Class names match their file names
     * - Directory structure mirrors namespace structure
     * 
     * @param string $dir The directory path to scan for rule classes
     * @param string $namespace The base namespace for the discovered classes
     * @return void
     * @throws InvalidArgumentException If the provided path is not a directory
     * @throws RuntimeException If a discovered class cannot be found or instantiated
     */
    public function registerFromDir(string $dir, string $namespace): void
    {
        if (!is_dir($dir)) {
            throw new InvalidArgumentException("Provided path is not a directory: $dir");
        }

        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

        foreach ($rii as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filePath = $file->getRealPath();

                // Get path relative to base directory
                $relativePath = substr($filePath, strlen(rtrim($dir, DIRECTORY_SEPARATOR)) + 1);

                // Convert directory separators to namespace separators
                $className = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

                // Remove the .php extension
                $className = substr($className, 0, -4);

                // Compose fully qualified class name
                $fqn = rtrim($namespace, '\\') . '\\' . $className;

                // Normalize namespace separators (in case)
                $fqn = str_replace('\\\\', '\\', $fqn);

                // Instantiate class (assumes no-argument constructor)
                if (class_exists($fqn)) {
                    $instance = new $fqn();

                    // Register the instance using this class's register method
                    $this->register($instance);
                } else {
                    throw new RuntimeException("Class $fqn not found.");
                }
            }
        }
    }


    /**
     * Register a single validation rule
     * 
     * Registers an individual rule by storing it with its name as the key.
     * The rule's test method will be called when the rule is resolved.
     * 
     * @param RuleInterface $rule The rule to register
     * @return void
     * @throws InvalidArgumentException If a rule with the same name is already registered
     */
    protected function registerRule(RuleInterface $rule): void
    {
        // Get the rule's identifier name
        $name = $rule->name();

        // Check for naming conflicts
        if (isset($this->rules[$name])) {
            throw new InvalidArgumentException("A rule named : '$name' is already registered.");
        }

        // Store rule with 'test' as the method to call
        $this->rules[$name] = [$rule, 'test'];
    }

    /**
     * Register multiple validation rules from a rule list
     * 
     * Processes a RuleListInterface object that provides multiple rules.
     * The provides() method returns an array where:
     * - Numeric keys: value is both rule name and method name
     * - String keys: key is rule name, value is method name
     * 
     * @param RuleListInterface $ruleList The rule list to register
     * @return void
     */
    protected function registerRuleList(RuleListInterface $ruleList): void
    {
        // Iterate through all rules provided by the rule list
        foreach ($ruleList->provides() as $key => $value) {
            if (is_int($key)) {
                // Numeric key → $value is the rule name, method name same as rule name
                $this->rules[$value] = [$ruleList, $value];
            } else {
                // String key → $key is the rule name, $value is the method name
                $this->rules[$key] = [$ruleList, $value];
            }
        }
    }

    /**
     * Resolve a registered rule by name
     * 
     * Returns the callable array for a registered rule, which can be used
     * to invoke the validation logic. The returned array contains the object
     * instance and method name that should be called.
     * 
     * @param string $ruleName The name of the rule to resolve
     * @return callable|array Array containing [object_instance, method_name]
     * @throws InvalidArgumentException If no rule is registered with the given name
     */
    public function resolve(string $ruleName): callable|array
    {
        // Check if the rule exists
        if (!isset($this->rules[$ruleName])) {
            throw new InvalidArgumentException("No rule registered with name: '{$ruleName}'.");
        }

        // Return the callable array [object, method]
        return $this->rules[$ruleName];
    }
}
