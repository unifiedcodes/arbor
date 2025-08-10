<?php

namespace Arbor\validation;

/**
 * DSL Parser for Validation Rules
 * 
 * This class provides functionality to parse validation rules from both string and array formats
 * into a standardized Abstract Syntax Tree (AST) structure. The parser supports:
 * - OR operations using '||' delimiter in strings
 * - AND operations using whitespace separation in strings  
 * - Rule negation using '!' prefix
 * - Rule parameters using ':' delimiter followed by comma-separated values
 * 
 * Example string format: "required email || phone !empty"
 * Example array format: [['required', 'email' => []], ['phone', '!empty']]
 * 
 * The resulting AST structure is:
 * [
 *     [  // OR group 1
 *         ['rule' => 'required', 'negate' => false, 'params' => []],
 *         ['rule' => 'email', 'negate' => false, 'params' => []]
 *     ],
 *     [  // OR group 2  
 *         ['rule' => 'phone', 'negate' => false, 'params' => []],
 *         ['rule' => 'empty', 'negate' => true, 'params' => []]
 *     ]
 * ]
 * 
 * @package Arbor\validation
 * 
 */
class Parser
{

    /**
     * Main entry point for parsing validation rules
     * Determines input type and delegates to appropriate parsing method
     * 
     * @param string|array $input The validation rules to parse
     * @return array The parsed AST structure
     * @throws \InvalidArgumentException When input is neither string nor array
     */
    public function parse(string|array $input): array
    {
        // checks if input is string -> delegate to parseStr
        // if input is array -> delegate to parseArr

        if (is_string($input)) {
            return $this->parseStr($input);
        }

        if (is_array($input)) {
            return $this->parseArr($input);
        }

        throw new \InvalidArgumentException('DSL input must be a string or array.');
    }

    /**
     * Parses string-based validation rule definitions
     * 
     * String format supports:
     * - OR groups separated by '||' 
     * - AND conditions separated by whitespace within each OR group
     * - Individual rules with optional parameters and negation
     * 
     * Example: "required email:domain.com || phone !empty:trim"
     * 
     * @param string $input The validation rule string to parse
     * @return array The parsed AST structure
     */
    public function parseStr(string $input): array
    {
        // break into or groups.
        // break into and groups.
        // delegate each and segment to parseRule.
        $ast = [];


        // Split on '||' to create OR groups - each group represents an alternative validation path
        $orGroups = explode('||', $input);

        foreach ($orGroups as $orGroup) {
            // Split each OR group on whitespace to get individual AND-ed rules
            $andParts = preg_split('/\s+/', trim($orGroup));
            $parsedGroup = [];

            // Parse each individual rule within this AND group
            foreach ($andParts as $rule) {
                $parsedGroup[] = $this->parseRule($rule);
            }

            // Add this parsed AND group to the AST
            $ast[] = $parsedGroup;
        }

        return $ast;
    }

    /**
     * Parses an individual validation rule string
     * 
     * Handles rule format: rulename:param1,param2,param3
     * Also supports negation with '!' prefix
     * 
     * Examples:
     * - "required" -> rule with no parameters
     * - "min:5" -> rule with single parameter
     * - "between:1,10" -> rule with multiple parameters
     * - "!empty" -> negated rule
     * 
     * @param string $rule The individual rule string to parse
     * @return array Parsed rule node structure
     */
    protected function parseRule(string $rule): array
    {
        // check if string contains colon.
        // create a params array from the string after colon
        // delegate to makeNode
        $params = [];

        // Check if rule has parameters (indicated by colon)
        if (str_contains($rule, ':')) {

            // Split on first colon only - rule name vs parameters
            [$rule, $paramStr] = explode(':', $rule, 2);


            if (isset($paramStr) && trim($paramStr) !== '') {
                // Parse comma-separated parameters and trim whitespace
                $params = array_map('trim', explode(',', $paramStr));
            }
        }

        // Create the standardized node structure
        return $this->makeNode($rule, $params);
    }

    /**
     * Creates a standardized validation rule node structure
     * 
     * Handles negation detection and creates consistent AST node format.
     * All rules are normalized to the same structure regardless of input format.
     * 
     * @param string $rule The rule name (potentially with '!' prefix for negation)
     * @param array $params Optional array of rule parameters
     * @return array Standardized rule node with 'rule', 'negate', and 'params' keys
     */
    protected function makeNode(string $rule, array $params = []): array
    {
        // Check for negation prefix
        $negate = false;
        if (str_starts_with($rule, '!')) {
            $negate = true;
            $rule = substr($rule, 1); // Remove the '!' prefix
        }

        // Return standardized node structure
        return [
            'rule' => $rule,      // The rule name without negation prefix
            'negate' => $negate,  // Boolean flag indicating if rule should be negated
            'params' => $params,  // Array of parameters for this rule
        ];
    }

    /**
     * Parses array-based validation rule definitions
     * 
     * Supports multiple array formats:
     * 1. Simple flat array: ['required', 'email'] 
     * 2. Associative with parameters: ['min' => [5], 'max' => [100]]
     * 3. Nested OR groups: [['required', 'email'], ['phone']]
     * 
     * The method normalizes the input to ensure consistent processing.
     * 
     * @param array $input The validation rules array to parse
     * @return array The parsed AST structure
     */
    public function parseArr(array $input): array
    {
        $ast = [];

        // Normalize input - if not already a list of groups, wrap it as a single group
        // This handles cases like ['required', 'email'] vs [['required', 'email'], ['phone']]
        if (!(array_is_list($input) && isset($input[0]) && is_array($input[0]))) {
            $input = [$input]; // Wrap single rule group in array
        }

        // Parse each OR group
        foreach ($input as $group) {
            $ast[] = $this->parseGroup($group);
        }

        return $ast;
    }

    /**
     * Parses a single OR group from array input
     * 
     * Handles two array formats within a group:
     * 1. Numeric keys (simple rule names): [0 => 'required', 1 => 'email']
     * 2. String keys (rules with parameters): ['min' => [5], 'email' => ['domain.com']]
     * 
     * Mixed formats are supported within the same group.
     * 
     * @param array $group A single OR group containing validation rules
     * @return array Parsed group with standardized rule node structures
     */
    protected function parseGroup(array $group): array
    {
        $parsedGroup = [];

        foreach ($group as $key => $value) {
            if (is_int($key)) {
                // Numeric key: value is the rule name, no parameters
                // Example: [0 => 'required'] or [1 => '!empty']
                $parsedGroup[] = $this->makeNode($value);
            } else {
                // String key: key is rule name, value is parameters array
                // Example: ['min' => [5]] or ['between' => [1, 10]]
                $params = is_array($value) ? $value : [];
                $parsedGroup[] = $this->makeNode($key, $params);
            }
        }

        return $parsedGroup;
    }
}
