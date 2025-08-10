<?php

/**
 * Arbor Validation Contracts
 * 
 * This file contains the RuleListInterface contract for the Arbor validation system.
 * 
 * @package Arbor\contracts\validation
 * @author  Your Name
 * @version 1.0.0
 * @since   1.0.0
 */

namespace Arbor\contracts\validation;

/**
 * Rule List Interface
 * 
 * Defines the contract for classes that provide validation rules.
 * Implementations of this interface should return an array of validation
 * rules that can be used by the validation system.
 * 
 * This interface is typically implemented by rule providers that need to
 * supply a collection of validation rules to validators or rule engines.
 * 
 * @package Arbor\contracts\validation
 * 
 * @example
 * ```php
 * class UserValidationRules implements RuleListInterface
 * {
 *     public function provides(): array
 *     {
 *         return [
 *             'email' => 'required|email',
 *             'name' => 'required|string|max:255',
 *             'age' => 'required|integer|min:18'
 *         ];
 *     }
 * }
 * ```
 */
interface RuleListInterface
{
    /**
     * Get the validation rules provided by this rule list.
     * 
     * Returns an array of validation rules that this provider supplies.
     * The structure and format of the returned array should follow the
     * conventions established by the validation system.
     * 
     * Implementations should ensure that:
     * - The returned array is consistently formatted
     * - Rule names are unique within the returned set
     * - Rules are properly structured for the validation engine
     * 
     * @return array An array of validation rules provided by this implementation.
     *               The exact structure depends on the validation system's requirements,
     *               but typically contains rule names as keys and rule definitions as values.

     */
    public function provides(): array;
}
