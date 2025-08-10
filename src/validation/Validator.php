<?php


namespace Arbor\validation;


use Arbor\validation\Parser;
use Arbor\validation\Registry;
use Arbor\validation\Evaluator;
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

    protected array $errors = [];

    // facade class to delegate and orchestrate.
    public function __construct(
        Registry $registry,
        Parser $parser,
        Evaluator $evaluator
    ) {
        $this->registry = $registry;
        $this->parser = $parser;
        $this->evaluator = $evaluator;
    }


    public function check($input, $rule)
    {
        return $this->evaluator->evaluateSingle($input, $rule);
    }


    public function checkRules($input, $dsl)
    {
        $ast = $this->parser->parse($dsl);
        return $this->evaluator->evaluate($input, $ast);
    }


    public function checkBatch($inputs, $defintion)
    {
        // validate array of inputs with array of definition
    }


    protected function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }


    public function parseRules($dsl): array
    {
        return $this->parser->parse($dsl);
    }


    public function addRulesFromClass(RuleInterface|RuleListInterface $class)
    {
        $this->registry->register($class);
    }


    public function addRulesFromDir() {}
}
