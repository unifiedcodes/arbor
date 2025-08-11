<?php

namespace Arbor\validation;

use InvalidArgumentException;
use Arbor\validation\Evaluator;

/**
 * Definition class for validating data against predefined validation rules.
 * 
 * This class provides functionality to define validation rules and validate input data
 * against those rules using an Evaluator instance. It supports early breaking on
 * validation failures and returns comprehensive validation results.
 */
class Definition
{
    /**
     * The evaluator instance used to process validation rules
     *
     * @var Evaluator
     */
    protected Evaluator $evaluator;

    /**
     * Array containing the validation definition rules
     * Structure: ['field_name' => AST_array, ...]
     *
     * @var array
     */
    protected array $definition;

    /**
     * Flag to determine if validation should stop on first failure
     *
     * @var bool
     */
    protected bool $earlyBreak = false;

    /**
     * Constructor stores the Evaluator instance.
     *
     * @param Evaluator $evaluator The evaluator instance to use for validation
     */
    public function __construct(Evaluator $evaluator)
    {
        $this->evaluator = $evaluator;
    }

    /**
     * Set the early break behavior for validation.
     * 
     * When enabled, validation will stop on the first field that fails validation
     * instead of validating all fields.
     *
     * @param bool $is Whether to enable early breaking
     * @return self Returns this instance for method chaining
     */
    public function setEarlyBreak(bool $is): self
    {
        $this->earlyBreak = $is;
        return $this;
    }

    /**
     * Define the validation rules to be used.
     * 
     * Sets the validation definition array that maps field names to their
     * corresponding AST (Abstract Syntax Tree) validation rules.
     *
     * @param array $definition Associative array where keys are field names and values are AST arrays
     * @return self Returns this instance for method chaining
     */
    public function define(array $definition): self
    {
        $this->definition = $definition;
        return $this;
    }

    /**
     * Validate a set of inputs against a definition array.
     *
     * Validates the provided input data against the previously defined validation rules.
     * Returns a comprehensive result including overall validation status and field-specific errors.
     *
     * @param array $inputs Associative array of field => value pairs to validate
     * @return array Returns ['validated' => bool, 'errors' => array] where:
     *               - 'validated' is true if all fields passed validation
     *               - 'errors' is an associative array of field => error_array pairs
     * 
     * @throws InvalidArgumentException If validate is called before define() or if AST is invalid
     */
    public function validate(array $inputs): array
    {
        if (empty($this->definition)) {
            throw new InvalidArgumentException(
                "validate definition cannot be called before calling define method"
            );
        }

        $allValidated = true;
        $errors = [];

        foreach ($this->definition as $field => $ast) {
            $value = $inputs[$field] ?? null;

            // Ensure AST is actually an array before passing
            if (!is_array($ast)) {
                throw new InvalidArgumentException(
                    "Validation definition for '{$field}' must be an AST array."
                );
            }

            $result = $this->evaluator->evaluate($value, $ast);

            $errors[$field] = $result['errors'];

            if (!$result['validated']) {
                $allValidated = false;

                if ($this->earlyBreak) {
                    break;
                }
            }
        }

        return [
            'validated' => $allValidated,
            'errors'    => $errors
        ];
    }
}
