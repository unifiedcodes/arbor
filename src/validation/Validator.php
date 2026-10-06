<?php

namespace Arbor\validation;


use Exception;
use Arbor\validation\Parser;
use Arbor\validation\Registry;
use Arbor\validation\Evaluator;
use Arbor\validation\ErrorsFormatter;
use Arbor\validation\RuleInterface;
use Arbor\validation\RuleListInterface;

/**
 * Validator Orchestrator
 * 
 * Main validation class that acts as a orchestrator to orchestrate validation operations.
 * Provides a unified interface for validating single rules, multiple rules, and batch operations.
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
     * Array storing validation errors
     * 
     * @var array
     */
    protected array $errors = [];

    /**
     * Constructor - Initialize validator with required dependencies
     * 
     * Class to delegate and orchestrate validation operations across
     * different components (registry, parser, evaluator, definition).
     * 
     * @param bool $earlyBreak early break for evaluator.
     * 
     */
    public function __construct()
    {
        $this->registry = new Registry();
        $this->parser = new Parser();

        $this->evaluator = new Evaluator($this->registry);
    }

    /**
     * Validate input against multiple rules using DSL or array format
     * 
     * @param mixed $input The input data to validate
     * @param string|array $dsl The validation rules in DSL string or array format
     * @param string|null $name Optional name for error tracking
     * @return bool True if all validations pass, false otherwise
     */
    public function check(mixed $input, string|array $dsl): array
    {
        // Parse DSL into abstract syntax tree
        $ast = $this->parser->parse($dsl);
        // Evaluate the parsed rules against input
        return $this->evaluator->evaluate($input, $ast);
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
    public function checkDefinition(array $inputs, array $definition)
    {
        $definitionAst = $this->parser->parseDefinition($definition);
        return $this->evaluator->evaluateDefinition($inputs, $definitionAst);
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
    public function addRule(RuleInterface|RuleListInterface $class)
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
    public function addRulesDir(string $dir, string $namespace): void
    {
        $this->registry->registerFromDir($dir, $namespace);
    }
}
