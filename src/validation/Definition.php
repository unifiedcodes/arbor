<?php

namespace Arbor\validation;


use InvalidArgumentException;
use Arbor\validation\Evaluator;



class Definition
{
    protected Evaluator $evaluator;
    protected array $definition;
    protected bool $earlyBreak = false;

    /**
     * Constructor stores the Evaluator instance.
     *
     * @param Evaluator $evaluator
     */
    public function __construct(Evaluator $evaluator)
    {
        $this->evaluator = $evaluator;
    }


    public function setEarlyBreak(bool $is): self
    {
        $this->earlyBreak = $is;
        return $this;
    }


    public function define(array $definition): self
    {
        $this->definition = $definition;
        return $this;
    }


    /**
     * Validate a set of inputs against a definition array.
     *
     * @param array $inputs     Associative array of field => value
     * @param array $definition Associative array of field => AST/DSL
     * @return array Returns ['validated' => bool, 'errors' => array]
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
