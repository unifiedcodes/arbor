<?php

namespace Arbor\validation;

/**
 * ErrorsFormatter
 * 
 * Formats validation error arrays into human-readable error messages.
 * Handles complex error structures with AND/OR logic and removes duplicate messages.
 * 
 * @package Arbor\validation
 */
class ErrorsFormatter
{

    /**
     * Formats an array of validation errors into structured error messages.
     * 
     * Takes a multi-dimensional array where keys are field names and values are
     * arrays of error message groups, then formats each field's errors into
     * a readable string with AND/OR logic.
     * 
     * @param array $errors Multi-dimensional array of errors keyed by field name
     * @return array Formatted errors with field names as keys and formatted messages as values
     */
    public function format(array $errors): array
    {
        $formattedErrors = [];

        foreach ($errors as $field => $bucket) {
            $formattedErrors[$field] = $this->formatBucket($field, $bucket);
        }

        $formatted = array_filter($formattedErrors, fn($msg) => $msg !== '');

        return $formatted;
    }


    /**
     * Formats a single field's error bucket into a readable error message.
     * 
     * Processes error groups for a specific field, removes duplicates, and creates
     * a formatted message using AND/OR logic. Groups within an array are joined with
     * 'and', while separate groups are joined with 'Or'.
     * 
     * @param string $field The name of the field being validated
     * @param array $bucket Array of error message groups for this field
     * @return string Formatted error message in the format "'fieldname' message1 and message2 Or message3"
     */
    protected function formatBucket(string $field, array $bucket): string
    {
        $bucket = $this->removeDuplicates($bucket);

        $messages = [];

        foreach ($bucket as $andGroup) {

            // early return if any of the OR level errors are empty array, meaning validation was successful.
            if (empty($andGroup)) {
                return '';
            }

            $andMessage = implode(' and ', $andGroup);

            if (!empty($andMessage)) {
                $messages[] = $andMessage;
            }
        }

        return !empty($messages) ? "'$field' " . implode(' Or ', $messages) : '';
    }


    /**
     * Removes duplicate error messages from error groups while preserving structure.
     * 
     * Iterates through all error groups and removes any duplicate messages that have
     * already been seen, maintaining the original array structure but eliminating
     * redundant error messages across all groups.
     * 
     * @param array $groups Array of error message groups, where each group contains related messages
     * @return array The same structure with duplicate messages removed
     */
    function removeDuplicates(array $groups): array
    {
        $seen = [];

        foreach ($groups as $i => &$andGroup) {
            foreach ($andGroup as $key => $message) {
                if (in_array($message, $seen, true)) {
                    unset($andGroup[$key]);
                } else {
                    $seen[] = $message;
                }
            }
        }

        unset($andGroup); // break reference

        return $groups;
    }
}
