<?php

namespace Arbor\validation;

use Throwable;
use InvalidArgumentException;
use Arbor\validation\Registry;
use Arbor\validation\ValidationException;

/**
 * Evaluator class for processing validation rules against input data.
 * 
 * This class handles the evaluation of validation rules organized in an Abstract Syntax Tree (AST)
 * structure, where rules are grouped in AND/OR logic patterns. It supports early termination
 * for performance optimization and provides comprehensive error reporting.
 * 
 * @package Arbor\validation
 * 
 */
class Evaluator
{
    /** @var Registry Registry instance containing validation rule definitions */
    protected Registry $registry;


    /** @var bool Flag to enable early return optimization when first valid group is found */
    protected bool $earlyBreak = true;

    /**
     * Constructor initializes the evaluator with a validation registry.
     * 
     * @param Registry $registry The registry containing validation rule definitions
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Configure early return behavior for performance optimization.
     * 
     * When enabled, evaluation stops as soon as the first valid AND group is found,
     * potentially improving performance for complex validation trees.
     * 
     * @param bool $is True to enable early return, false to evaluate all groups
     */
    public function setEarlyBreak(bool $is): void
    {
        $this->earlyBreak = $is;
    }

    /**
     * Evaluate a single validation rule against input data.
     * 
     * This method provides a simplified interface for evaluating a single rule
     * without the complexity of AST processing. Returns both pass/fail status
     * and any error messages encountered.
     * 
     * @param mixed  $input The data to validate
     * @param string $rule  The name of the validation rule to apply
     * @param array  $params Additional parameters for the rule
     * @param bool   $negate Whether to negate the rule result
     * @return array Returns ['validated' => bool, 'errors' => array]
     */
    public function evaluateSingle(
        mixed $input,
        string $rule,
        array $params = [],
        bool $negate = false
    ): array {
        $errors = [];
        $validated = false;

        try {
            $validated = $this->evaluateRule($input, $rule, $params, $negate);
            if (!$validated) {
                $errors[] = "Rule '{$rule}' failed.";
            }
        } catch (ValidationException | Throwable $e) {
            $errors[] = $e->getMessage();
        }

        return [
            'validated' => $validated,
            'errors'    => $errors
        ];
    }

    /**
     * Evaluate a complete validation AST against input data.
     * 
     * The AST represents validation logic as an array of AND groups, where:
     * - The outer array represents OR logic (any group can pass)
     * - Each inner array represents AND logic (all rules must pass)
     * 
     * @param mixed $input The data to validate
     * @param array $ast The Abstract Syntax Tree containing validation rules
     * @return array Returns [bool $passed, array $errors] where:
     *               - $passed: true if at least one AND group passed validation
     *               - $errors: array of error messages grouped by AND group
     */
    public function evaluate(mixed $input, array $ast): array
    {
        $errors = [];
        $passed = false;

        // Iterate through each AND group (OR logic at top level)
        foreach ($ast as $andGroup) {
            [$groupPassed, $groupErrors] = $this->evaluateAndGroup($input, $andGroup);

            if ($groupPassed) {
                $passed = true;

                if ($this->earlyBreak) {
                    // Early termination: stop processing once we find a passing group
                    break;
                }
            }

            // Collect errors from this group for comprehensive reporting
            $errors[] = $groupErrors;
        }

        return [
            'validated' => $passed,
            'errors' => $errors
        ];
    }

    /**
     * Evaluate a single AND group of validation rules.
     * 
     * All rules within an AND group must pass for the group to be considered valid.
     * Processing stops early if earlyBreak is enabled and a rule fails.
     * 
     * @param mixed $input The data to validate
     * @param array $andGroup Array of rule nodes, each containing rule definition
     * @return array Returns [bool $allPassed, array $errors] where:
     *               - $allPassed: true if all rules in the group passed
     *               - $errors: array of error messages from failed rules
     */
    protected function evaluateAndGroup(mixed $input, array $andGroup): array
    {
        $errors = [];
        $allPassed = true;

        // Process each rule in the AND group
        foreach ($andGroup as $ruleNode) {
            try {
                $evaluatedRule = $this->evaluateRule(
                    $input,
                    $ruleNode['rule'],
                    $ruleNode['params'] ?? [],
                    $ruleNode['negate']
                );

                if (!$evaluatedRule) {
                    $allPassed = false;
                    // Generate a generic failure message for rule failures
                    $errors[] = "Rule '{$ruleNode['rule']}' failed.";
                }
            } catch (ValidationException $e) {
                // Handle validation exceptions with custom error messages
                $allPassed = false;
                $errors[] = $e->getMessage();

                if ($this->earlyBreak) {
                    // Early termination: return immediately on first failure
                    return [false, $errors];
                }
            }
        }

        return [$allPassed, $errors];
    }

    /**
     * Execute a single validation rule against input data.
     * 
     * This method resolves the rule from the registry, executes it with provided
     * parameters, and applies negation logic if specified.
     * 
     * @param mixed $input The data to validate
     * @param string $ruleName Name of the validation rule to execute
     * @param array $params Additional parameters to pass to the validation rule
     * @param bool $negate Whether to negate the rule result (NOT logic)
     * @return bool True if the rule passes (considering negation), false otherwise
     * @throws InvalidArgumentException If the rule cannot be resolved or is not callable
     */
    protected function evaluateRule(mixed $input, string $ruleName, array $params = [], bool $negate = false): bool
    {
        // Resolve the validation rule from the registry
        $callable = $this->registry->resolve($ruleName);

        if (!is_callable($callable)) {
            throw new InvalidArgumentException("Rule '{$ruleName}' is not callable.");
        }

        // Execute the validation rule with input and parameters
        $result = (bool) call_user_func($callable, $input, ...$params);

        // Apply negation logic if specified
        if ($negate) {
            $result = !$result;
        }

        return $result;
    }
}
