<?php

/**
 * PHP Utility Functions
 * 
 * This file contains a collection of utility functions for common operations
 * including cryptographic random number generation, token creation, path normalization,
 * directory management, URL processing, and configuration access.
 * 
 * All functions are conditionally defined to prevent redefinition errors.
 */

use Arbor\facades\Config;

/**
 * Generate a cryptographically secure random integer within a specified range.
 * 
 * This function provides a secure alternative to mt_rand() or rand() by using
 * PHP's random_int() function which is cryptographically secure.
 * 
 * @param int $min The minimum value (inclusive)
 * @param int $max The maximum value (inclusive)
 * @return int A cryptographically secure random integer between $min and $max
 * @throws Exception If it was not possible to gather sufficient entropy
 */
if (!function_exists('crypto_random')) {
    function crypto_random(int $min, int $max)
    {
        // Use a cryptographically secure random number generator
        $rnd = random_int($min, $max);
        return $rnd;
    }
}

/**
 * Generate a random token string with specified length and character types.
 * 
 * This function creates random tokens for various purposes such as API keys,
 * session tokens, passwords, or other security-related strings using 
 * cryptographically secure random number generation.
 * 
 * @param int $length The desired length of the generated token
 * @param string $type The type of characters to include in the token:
 *                     - 'numeric': Only digits (0-9)
 *                     - 'alpha': Only letters (a-z, A-Z)
 *                     - 'alphaNumeric': Letters and digits
 *                     - 'all' (default): Letters, digits, and symbols
 * @return string The generated random token
 * @throws Exception If crypto_random() fails to generate secure random numbers
 */
if (!function_exists('random_token')) {
    function random_token(int $length, string $type = 'all'): string
    {
        $alphabets = [
            'numeric'      => '0123456789',
            'alpha'        => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'symbols'      => '!@#$%^&*()-_=+[]{}|.<>?',
        ];

        // Build alphabet based on type
        switch (strtolower($type)) {
            case 'numeric':
                $characters = $alphabets['numeric'];
                break;

            case 'alpha':
                $characters = $alphabets['alpha'];
                break;

            case 'alphaNumeric':
                $characters = $alphabets['numeric'] . $alphabets['alpha'];
                break;

            case 'all':
            default:
                $characters = implode('', $alphabets); // use all categories
                break;
        }

        $token = '';
        $max = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[crypto_random(0, $max)];
        }

        return $token;
    }
}

/**
 * Normalize a directory path to use the system-specific directory separator.
 * 
 * This function standardizes directory paths by converting all forward and
 * backward slashes to the appropriate system directory separator, removing
 * duplicate separators, and ensuring the path ends with exactly one separator.
 * 
 * @param string $path The directory path to normalize
 * @return string The normalized directory path with trailing separator
 */
if (!function_exists('normalizeDirPath')) {
    function normalizeDirPath(string $path): string
    {
        // Replace all slashes with the system-specific separator
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        // Normalize double slashes
        $path = preg_replace('#' . preg_quote(DIRECTORY_SEPARATOR) . '+#', DIRECTORY_SEPARATOR, $path);

        // Trim trailing slashes and add exactly one
        return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}

/**
 * Normalize a file path to use the system-specific directory separator.
 * 
 * Similar to normalizeDirPath() but for file paths, ensuring no trailing
 * directory separator is present at the end of the path.
 * 
 * @param string $path The file path to normalize
 * @return string The normalized file path without trailing separator
 */
if (!function_exists('normalizeFilePath')) {
    function normalizeFilePath(string $path): string
    {
        // Replace all slashes with the system-specific separator
        $path = normalizeDirPath($path);
        // Trim trailing slashes for file
        return rtrim($path, DIRECTORY_SEPARATOR);
    }
}

/**
 * Ensure a directory exists, creating it if necessary.
 * 
 * This function checks if a directory exists and creates it (including parent
 * directories) if it doesn't. It also validates that the path is not an
 * existing file to prevent conflicts.
 * 
 * @param string $path The directory path to ensure exists
 * @return string The same directory path that was passed in
 * @throws InvalidArgumentException If the path exists but is a file
 * @throws RuntimeException If the directory creation fails
 */
if (!function_exists('ensureDir')) {
    function ensureDir(string $path): string
    {
        if (is_file($path)) {
            throw new \InvalidArgumentException("Path '$path' is a file, not a directory.");
        }

        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                throw new \RuntimeException("Failed to create directory: $path");
            }
        }

        return $path;
    }
}

/**
 * Normalize URL slashes by removing duplicate forward slashes.
 * 
 * This function cleans up URLs by removing multiple consecutive forward
 * slashes while preserving the protocol separator (://) and trimming
 * whitespace from the URL.
 * 
 * @param string $url The URL to normalize
 * @return string The normalized URL with single forward slashes
 */
if (!function_exists('normalizeURLSlashes')) {
    function normalizeURLSlashes(string $url): string
    {
        return preg_replace('#(?<!:)//+#', '/', trim($url));
    }
}

/**
 * Retrieve a configuration value by key.
 * 
 * This function provides a convenient helper to access configuration values
 * through the Arbor Config facade. It serves as a shorthand for Config::get().
 * 
 * @param string $key The configuration key to retrieve (supports dot notation)
 * @return mixed The configuration value, or null if the key doesn't exist
 */
if (!function_exists('config')) {
    function config(string $key): mixed
    {
        return Config::get($key);
    }
}


if (!function_exists('joinPath')) {
    function joinPath(string ...$segments): string
    {
        $clean = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === null) {
                continue;
            }

            $clean[] = trim($segment, "/\\");
        }

        return implode('/', $clean);
    }
}
