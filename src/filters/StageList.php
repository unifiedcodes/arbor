<?php

namespace Arbor\filters;

use Arbor\contracts\filters\StageListInterface;

/**
 * StageList provides a collection of data filtering and sanitization stages
 * that can be used in a pipeline processing system.
 * 
 * This class implements the StageListInterface and offers common data
 * transformation filters such as trimming, case conversion, sanitization,
 * and validation. Each method follows the middleware pattern, accepting
 * input data and a callable next function to continue the pipeline.
 */
class StageList implements StageListInterface
{
    /**
     * Returns an array of all available filter stage names provided by this class.
     * 
     * @return array List of filter method names available for use
     */
    public function provides(): array
    {
        return [
            'nullIfEmpty',
            'lowercase',
            'removeExtraSpaces',
            'sanitizeEmail',
            'sanitizeNumber',
            'stripTags',
            'trim',
            'uppercase'
        ];
    }

    /**
     * Converts empty strings or empty arrays to null values.
     * 
     * This filter is useful for normalizing empty data to null,
     * which can be helpful for database operations and validation.
     * 
     * @param mixed $input The input data to process
     * @param callable $next The next stage in the pipeline
     * @return mixed The processed input passed to the next stage
     */
    public function nullIfEmpty(mixed $input, callable $next): mixed
    {
        if ($input === '' || $input === []) {
            $input = null;
        }

        return $next($input);
    }

    /**
     * Converts string input to lowercase using multibyte-safe functions.
     * 
     * Only processes string inputs, leaving other data types unchanged.
     * Uses mb_strtolower() to properly handle Unicode characters.
     * 
     * @param mixed $input The input data to process
     * @param callable $next The next stage in the pipeline
     * @return mixed The processed input passed to the next stage
     */
    public function lowercase(mixed $input, callable $next): mixed
    {
        if (is_string($input)) {
            $input = mb_strtolower($input);
        }

        return $next($input);
    }

    /**
     * Removes extra whitespace characters from strings.
     * 
     * Replaces multiple consecutive whitespace characters (spaces, tabs, newlines)
     * with a single space. Only processes string inputs.
     * 
     * @param mixed $input The input data to process
     * @param callable $next The next stage in the pipeline
     * @return mixed The processed input passed to the next stage
     */
    public function removeExtraSpaces(mixed $input, callable $next): mixed
    {
        if (is_string($input)) {
            $input = preg_replace('/\s+/', ' ', $input);
        }

        return $next($input);
    }

    /**
     * Sanitizes email addresses by removing invalid characters.
     * 
     * Uses PHP's FILTER_SANITIZE_EMAIL filter to remove characters
     * that are not valid in email addresses. Only processes string inputs.
     * 
     * @param mixed $input The input data to process
     * @param callable $next The next stage in the pipeline
     * @return mixed The processed input passed to the next stage
     */
    public function sanitizeEmail(mixed $input, callable $next): mixed
    {
        if (is_string($input)) {
            $input = filter_var($input, FILTER_SANITIZE_EMAIL);
        }

        return $next($input);
    }

    /**
     * Sanitizes numeric strings by removing non-numeric characters.
     * 
     * Removes all characters except digits (0-9) and decimal points (.).
     * This is useful for cleaning up numeric input that may contain
     * formatting characters or other unwanted text. Only processes string inputs.
     * 
     * @param mixed $input The input data to process
     * @param callable $next The next stage in the pipeline
     * @return mixed The processed input passed to the next stage
     */
    public function sanitizeNumber(mixed $input, callable $next): mixed
    {
        if (is_string($input)) {
            $input = preg_replace('/[^\d.]/', '', $input);
        }

        return $next($input);
    }

    /**
     * Removes HTML and PHP tags from strings.
     * 
     * Uses PHP's strip_tags() function to remove all HTML and PHP tags
     * from the input string. Only processes string inputs.
     * 
     * @param mixed $input The input data to process
     * @param callable $next The next stage in the pipeline
     * @return mixed The processed input passed to the next stage
     */
    public function stripTags(mixed $input, callable $next): mixed
    {
        if (is_string($input)) {
            $input = strip_tags($input);
        }

        return $next($input);
    }

    /**
     * Removes whitespace from the beginning and end of strings.
     * 
     * Uses PHP's trim() function to remove whitespace characters
     * from both ends of the string. Only processes string inputs.
     * 
     * @param mixed $input The input data to process
     * @param callable $next The next stage in the pipeline
     * @return mixed The processed input passed to the next stage
     */
    public function trim(mixed $input, callable $next): mixed
    {
        if (is_string($input)) {
            $input = trim($input);
        }

        return $next($input);
    }

    /**
     * Converts string input to uppercase using multibyte-safe functions.
     * 
     * Only processes string inputs, leaving other data types unchanged.
     * Uses mb_strtoupper() to properly handle Unicode characters.
     * 
     * @param mixed $input The input data to process
     * @param callable $next The next stage in the pipeline
     * @return mixed The processed input passed to the next stage
     */
    public function uppercase(mixed $input, callable $next): mixed
    {
        if (is_string($input)) {
            $input = mb_strtoupper($input);
        }

        return $next($input);
    }
}
