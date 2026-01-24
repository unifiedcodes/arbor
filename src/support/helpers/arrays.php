<?php

/**
 * Retrieve a value from an array or object using "dot" notation.
 * 
 * This function provides a convenient way to access nested values in arrays
 * and objects using dot notation (e.g., 'user.profile.name'). If the key
 * doesn't exist at any level, a default value is returned instead.
 * 
 * @param mixed $target The array or object to search within
 * @param string|null $key The key in dot notation (e.g., 'user.profile.name').
 *                         If null, returns the entire target.
 * @param mixed $default The default value to return if the key is not found
 * @return mixed The value found at the specified key path, or the default value
 */
if (!function_exists('value_at')) {
    function value_at(mixed $target, ?string $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $target;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return $default;
            }
        }

        return $target;
    }
}

/**
 * Set a value in a nested array using "dot" notation.
 * 
 * This function allows you to set values deep within a nested array structure
 * using dot notation (e.g., 'user.profile.name'). It will automatically create
 * any missing intermediate array levels needed to reach the target path.
 * The target array is modified by reference.
 * 
 * @param array &$target The array to modify (passed by reference)
 * @param string $path The key path in dot notation where the value should be set
 * @param mixed $value The value to set at the specified path
 * @return void
 * 
 */
if (!function_exists('value_set_at')) {
    function value_set_at(array &$target, string $path, mixed $value): void
    {
        $segments = explode('.', $path);
        $current = &$target;

        foreach ($segments as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }

        $current = $value;
    }
}
