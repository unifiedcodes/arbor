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
    protected Registry $registry;
    protected Parser $parser;
    protected Evaluator $evaluator;
    protected Definition $definition;

    protected array $errors = [];

    // facade class to delegate and orchestrate.
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


    public function validateRule(mixed $value, string $rule, ?string $name = null): bool
    {
        $result = $this->evaluator->evaluateSingle($value, $rule);

        // merge errors
        if ($name) {
            $this->errors[$name] = $result['errors'];
        } else {
            $this->errors[] = $result['errors'];
        }

        return $result['validated'];
    }


    public function validateRules(mixed $input, string|array $dsl, ?string $name = null): bool
    {
        $ast = $this->parser->parse($dsl);

        $result = $this->evaluator->evaluate($input, $ast);

        // merge errors
        if ($name) {
            $this->errors[$name] = $result['errors'];
        } else {
            $this->errors[] = $result['errors'];
        }

        return $result['validated'];
    }


    public function validateBatch(array $inputs, array $definition)
    {
        // parse definition.
        // set definition to validator.
        // validate input.
        $this->errors = [];

        $parsedDefinition = $this->parser->parseDefinition($definition);

        $this->definition->define($parsedDefinition);

        $result = $this->definition->validate($inputs);

        $this->errors = $result['errors'];

        return $result['validated'];
    }


    public function define(array $definition)
    {
        $parsedDefinition = $this->parser->parseDefinition($definition);
        $this->definition->define($parsedDefinition);
    }


    public function parseRules($dsl): array
    {
        return $this->parser->parse($dsl);
    }


    public function addRulesFromClass(RuleInterface|RuleListInterface $class)
    {
        $this->registry->register($class);
    }


    public function addRulesFromDir(string $dir, string $namespace): void
    {
        $this->registry->registerFromDir($dir, $namespace);
    }


    public function getErrors()
    {
        return $this->errors;
    }

    public function getReadableErrors() {}
}
