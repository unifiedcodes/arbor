<?php

namespace Arbor\validation;

use Arbor\validation\Parser;
use Arbor\validation\Registry;
use Arbor\validation\RuleList;
use Arbor\validation\Definition;
use Arbor\validation\Evaluator;
use Arbor\contracts\validation\RuleInterface;
use Arbor\contracts\validation\RuleListInterface;

/**
 * ValidatorFactory - Factory class for creating Validator instances
 * 
 * This factory class provides a fluent interface for configuring and creating
 * Validator instances with custom rules and dependencies. It follows the factory
 * design pattern to encapsulate the creation logic of complex Validator objects.
 * 
 * The factory manages three core dependencies:
 * - Registry: Stores and manages validation rules
 * - Parser: Parses validation rule definitions
 * - Evaluator: Executes validation logic against data
 * 
 * @package Arbor\validation
 */
class ValidatorFactory
{
    /**
     * Registry instance for storing validation rules
     * 
     * @var Registry
     */
    protected Registry $registry;

    /**
     * Parser instance for parsing rule definitions
     * 
     * @var Parser
     */
    protected Parser $parser;

    /**
     * Evaluator instance for evaluating validation dsl or rule
     * 
     * @var Evaluator
     */
    protected Evaluator $evaluator;

    /**
     * Definition instance for filtering and validating input definition
     * 
     * @var Definition
     */
    protected Definition $definition;

    protected bool $keepErrors = true;

    /**
     * Constructor - Initialize the factory with required dependencies
     * 
     * @param Registry $registry The rule registry for storing validation rules
     * @param Parser $parser The parser for processing rule definitions
     */
    public function __construct(
        Registry $registry,
        Parser $parser,
    ) {
        $this->registry = $registry;
        $this->parser = $parser;
        $this->evaluator = new Evaluator($this->registry);
        $this->definition = new Definition($this->evaluator);
    }

    /**
     * Register base validation rules
     * 
     * Adds the default set of validation rules to the registry by instantiating
     * and registering a new RuleList. This method provides a fluent interface
     * by returning the factory instance.
     * 
     * @return self Returns the factory instance for method chaining
     */
    public function withBaseRules(): self
    {
        // registering base rules.
        $this->registry->register(new RuleList());

        return $this;
    }


    /**
     * Register custom validation rules
     * 
     * Allows registration of custom validation rules by accepting either a single
     * rule (RuleInterface) or a collection of rules (RuleListInterface).
     * 
     * @param RuleInterface|RuleListInterface $class The rule or rule list to register
     * @return void
     */
    public function registerRule(RuleInterface|RuleListInterface $class)
    {
        $this->registry->register($class);
    }

    /**
     * Configure early break behavior for validation
     * 
     * Sets whether validation should stop on the first error encountered (early break)
     * or continue validating all rules. This affects both the evaluator and definition
     * components. Provides a fluent interface for method chaining.
     * 
     * @param bool $is True to enable early break, false to validate all rules
     * @return self Returns the factory instance for method chaining
     */
    public function setEarlyBreak(bool $is): self
    {
        $this->evaluator->setEarlyBreak($is);
        $this->definition->setEarlyBreak($is);

        return $this;
    }

    /**
     * Configure error retention behavior
     * 
     * Sets whether the validator should keep errors from every subsequent validation
     * or reset them. This setting is applied to new validator instances and resets
     * to default (true) after each validator creation.
     * 
     * @param bool $is True to keep errors across validations, false to reset them
     * @return void
     */
    public function keepErrors(bool $is)
    {
        // set if validator can keep errors from every subsequent validation or not.
        // resets on new validator creations to default.
        $this->keepErrors = $is;
    }

    /**
     * Create a new Validator instance
     * 
     * Instantiates and returns a new Validator object configured with the
     * factory's registry, parser, and evaluator dependencies.
     * 
     * @return Validator A configured validator instance
     */
    public function create(): Validator
    {

        $validator = new Validator(
            $this->registry,
            $this->parser,
            $this->evaluator,
            $this->definition,
        );

        $validator->keepErrors($this->keepErrors);

        // reset to default.
        $this->keepErrors = true;

        return $validator;
    }

    /**
     * Magic method to create Validator instance when factory is invoked as function
     * 
     * Provides a convenient shorthand for creating validators by making the factory
     * instance callable. This delegates to the create() method.
     * 
     * @return Validator A configured validator instance
     */
    public function __invoke(): Validator
    {
        return $this->create();
    }
}
