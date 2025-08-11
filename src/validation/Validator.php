<?php

namespace Arbor\validation;

use Arbor\validation\Parser;
use Arbor\validation\Registry;
use Arbor\validation\Evaluator;
use Arbor\validation\Definition;
use Arbor\contracts\validation\RuleInterface;
use Arbor\contracts\validation\RuleListInterface;

/**
 * Validator Facade
 * 
 * Main validation class that acts as a facade to orchestrate validation operations.
 * Provides a unified interface for validating single rules, multiple rules, and batch operations.
 * 
 * WIP: This class is 99% complete.
 * Pending:
 * - Implement checkBatch()
 * - Implement addRulesFromDir()
 * - Comment Documentation
 * 
 * @package Arbor\validation
 */
class Validator
{
    /**
     * Registry instance for managing validation rules
     * 
     * @var Registry
     */
    protected Registry $registry;

    /**
     * Parser instance for parsing DSL and definitions
     * 
     * @var Parser
     */
    protected Parser $parser;

    /**
     * Evaluator instance for executing validation logic
     * 
     * @var Evaluator
     */
    protected Evaluator $evaluator;

    /**
     * Definition instance for handling batch validation definitions
     * 
     * @var Definition
     */
    protected Definition $definition;

    /**
     * Flag to determine if errors should be accumulated across validations
     * 
     * @var bool
     */
    protected bool $keepErrors = true;

    /**
     * Array storing validation errors
     * 
     * @var array
     */
    protected array $errors = [];

    /**
     * Constructor - Initialize validator with required dependencies
     * 
     * Facade class to delegate and orchestrate validation operations across
     * different components (registry, parser, evaluator, definition).
     * 
     * @param Registry $registry Rule registry for managing validation rules
     * @param Parser $parser DSL and definition parser
     * @param Evaluator $evaluator Validation logic evaluator
     * @param Definition $definition Batch validation definition handler
     */
    public function __construct(
        Registry $registry,
        Parser $parser,
        Evaluator $evaluator,
        Definition $definition
    ) {
        $this->registry = $registry;
        $this->parser = $parser;
        $this->evaluator = $evaluator;
        $this->definition = $definition;
    }

    /**
     * Configure error accumulation behavior
     * 
     * When set to false, errors array will be cleared before each validation.
     * When set to true (default), errors will accumulate across validations.
     * 
     * @param bool $is Whether to keep errors across validations
     * @return void
     */
    public function keepErrors(bool $is)
    {
        $this->keepErrors = $is;
    }

    /**
     * Validate a single value against a single rule
     * 
     * @param mixed $value The value to validate
     * @param string $rule The validation rule to apply
     * @param string|null $name Optional name for error tracking (if provided, errors are keyed by name)
     * @return bool True if validation passes, false otherwise
     */
    public function validateRule(mixed $value, string $rule, ?string $name = null): bool
    {
        // Clear errors if not keeping them across validations
        if (!$this->keepErrors) {
            $this->errors = [];
        }

        // Evaluate the single rule against the value
        $result = $this->evaluator->evaluateSingle($value, $rule);

        // Store errors with appropriate key structure
        if ($name) {
            $this->errors[$name] = $result['errors'];
        } else {
            $this->errors[] = $result['errors'];
        }

        return $result['validated'];
    }

    /**
     * Validate input against multiple rules using DSL or array format
     * 
     * @param mixed $input The input data to validate
     * @param string|array $dsl The validation rules in DSL string or array format
     * @param string|null $name Optional name for error tracking
     * @return bool True if all validations pass, false otherwise
     */
    public function validateRules(mixed $input, string|array $dsl, ?string $name = null): bool
    {
        // Clear errors if not keeping them across validations
        if (!$this->keepErrors) {
            $this->errors = [];
        }

        // Parse DSL into abstract syntax tree
        $ast = $this->parser->parse($dsl);

        // Evaluate the parsed rules against input
        $result = $this->evaluator->evaluate($input, $ast);

        // Store errors with appropriate key structure
        if ($name) {
            $this->errors[$name] = $result['errors'];
        } else {
            $this->errors[] = $result['errors'];
        }

        return $result['validated'];
    }

    /**
     * Validate multiple inputs against a batch definition
     * 
     * This method handles complex validation scenarios where multiple inputs
     * need to be validated according to a structured definition.
     * 
     * @param array $inputs Array of input data to validate
     * @param array $definition Validation definition structure
     * @return bool True if all batch validations pass, false otherwise
     */
    public function validateBatch(array $inputs, array $definition)
    {
        // Clear errors for batch operation
        $this->errors = [];

        // Parse the validation definition into a usable format
        $parsedDefinition = $this->parser->parseDefinition($definition);

        // Set the parsed definition in the definition handler
        $this->definition->define($parsedDefinition);

        // Execute batch validation
        $result = $this->definition->validate($inputs);

        // Store batch validation errors
        $this->errors = $result['errors'];

        return $result['validated'];
    }

    /**
     * Define validation rules for later use
     * 
     * Allows pre-defining validation schemas that can be reused
     * without having to parse them repeatedly.
     * 
     * @param array $definition The validation definition to parse and store
     * @return void
     */
    public function define(array $definition)
    {
        $parsedDefinition = $this->parser->parseDefinition($definition);
        $this->definition->define($parsedDefinition);
    }

    /**
     * Parse validation rules DSL into abstract syntax tree
     * 
     * Utility method to parse rules without executing validation.
     * Useful for debugging or pre-processing validation rules.
     * 
     * @param mixed $dsl The DSL to parse (string or array format)
     * @return array Parsed abstract syntax tree representation
     */
    public function parseRules($dsl): array
    {
        return $this->parser->parse($dsl);
    }

    /**
     * Register validation rules from a class instance
     * 
     * Accepts either a single rule (RuleInterface) or a collection of rules (RuleListInterface)
     * and registers them with the rule registry for use in validations.
     * 
     * @param RuleInterface|RuleListInterface $class Rule class instance to register
     * @return void
     */
    public function addRulesFromClass(RuleInterface|RuleListInterface $class)
    {
        $this->registry->register($class);
    }

    /**
     * Register validation rules from a directory
     * 
     * Scans a directory for rule classes and registers them automatically.
     * Useful for bulk registration of custom validation rules.
     * 
     * @param string $dir Directory path to scan for rule classes
     * @param string $namespace Namespace prefix for the discovered classes
     * @return void
     */
    public function addRulesFromDir(string $dir, string $namespace): void
    {
        $this->registry->registerFromDir($dir, $namespace);
    }

    /**
     * Get all accumulated validation errors
     * 
     * Returns the raw errors array containing all validation failures
     * from the current or accumulated validation operations.
     * 
     * @return array Array of validation errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get human-readable validation errors
     * 
     * TODO: Implementation pending - should format errors into user-friendly messages
     * 
     * @return mixed Formatted error messages (implementation pending)
     */
    public function getReadableErrors() {}
}
