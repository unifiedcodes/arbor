<?php

namespace Arbor\validation;

use InvalidArgumentException;
use RuntimeException;

/**
 * RuleInterface - Contract for validation rules in the Arbor framework
 * 
 * This interface defines the contract that all validation rules must implement.
 * It provides a standardized way to create and execute validation logic across
 * the application, ensuring consistency and maintainability.
 * 
 * @package Arbor\validation
 */


/**
 * Interface RuleInterface
 * 
 * Defines the contract for validation rules that can be used throughout
 * the Arbor framework's validation system. All custom validation rules
 * must implement this interface.
 * 
 * Example implementation:
 * ```php
 * class EmailRule implements RuleInterface
 * {
 *     private string $value;
 *     
 *     public function __construct(string $value)
 *     {
 *         $this->value = $value;
 *     }
 *     
 *     public function name(): string
 *     {
 *         return 'email';
 *     }
 *     
 *     public function test(): bool
 *     {
 *         return filter_var($this->value, FILTER_VALIDATE_EMAIL) !== false;
 *     }
 * }
 * ```
 */
interface RuleInterface
{
    /**
     * Returns the unique name/identifier for this validation rule
     * 
     * This method should return a unique string identifier for the validation rule.
     * The name is typically used for error message generation, rule identification,
     * and debugging purposes. It should be descriptive and follow naming conventions.
     * 
     * @return string The unique name of the validation rule (e.g., 'required', 'email', 'min_length')
     * 
     * @example
     * ```php
     * public function name(): string
     * {
     *     return 'email_validation';
     * }
     * ```
     */
    public function name(): string;

    /**
     * Executes the validation test and returns the result
     * 
     * This method contains the core validation logic and should return true
     * if the validation passes (data is valid) or false if it fails (data is invalid).
     * The implementation should be stateless where possible and not have side effects.
     * 
     * @return bool True if validation passes, false if validation fails
     * 
     * @throws InvalidArgumentException When invalid data is provided to validate
     * @throws RuntimeException When validation cannot be performed due to system issues
     * 
     * @example
     * ```php
     * public function test(): bool
     * {
     *     // Perform validation logic
     *     return $this->value !== null && $this->value !== '';
     * }
     * ```
     */
    public function test(): bool;
}
