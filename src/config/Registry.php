<?php

namespace Arbor\config;

use Arbor\support\Macros;
use Exception;

/**
 * Registry class for managing application configuration.
 * 
 * Provides methods to load, merge, get, and set configuration values
 * with support for environment-specific configurations and dot notation access.
 * 
 * @package Arbor\config
 */
class Registry
{
    use Macros;

    /**
     * The configuration data storage.
     *
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Load configuration files from a directory.
     *
     * Reads all PHP files from the specified directory and stores their returned
     * arrays in the config registry. Files should return associative arrays.
     *
     * @param string $configPath The path to the configuration directory
     * @param bool $merge Whether to merge with existing configuration (default: false)
     * @return void
     * @throws Exception If the directory doesn't exist or if a config file doesn't return an array
     */
    public function loadConfig(string $configPath, bool $merge = false): void
    {
        $configPath = rtrim($configPath, '/') . '/';

        if (!is_dir($configPath)) {
            throw new Exception("Config directory not found: $configPath");
        }

        foreach (glob($configPath . '*.php') as $file) {
            $key = basename($file, '.php');
            try {
                $data = require $file;
            } catch (Exception $e) {
                throw new Exception("Error loading config file '$file': " . $e->getMessage());
            }

            if (!is_array($data)) {
                throw new Exception("Config file '$file' must return an array.");
            }

            if ($merge && isset($this->config[$key])) {
                // Merge environment config over base config for the same key
                $this->config[$key] = $this->arrayMergeRecursiveDistinct($this->config[$key], $data);
            } else {
                $this->config[$key] = $data;
            }
        }
    }

    /**
     * Recursively merge two arrays with distinct handling for different array types.
     *
     * Supports a special '!replace' flag to completely replace the base array.
     * List arrays are merged using array_merge, while associative arrays are
     * merged recursively with override values taking precedence.
     *
     * @param array<mixed> $base The base array
     * @param array<mixed> $override The array to merge over the base
     * @return array<mixed> The merged array
     */
    protected function arrayMergeRecursiveDistinct(array $base, array $override): array
    {
        // Replace entire base array with override
        if (isset($override['!replace']) && $override['!replace'] === true) {
            unset($override['!replace']);
            return $override;
        }

        // If both are list arrays, merge as lists
        if (array_is_list($base) && array_is_list($override)) {
            return array_merge($base, $override);
        }

        foreach ($override as $key => $value) {
            if (array_key_exists($key, $base)) {
                if (is_array($base[$key]) && is_array($value)) {
                    $base[$key] = $this->arrayMergeRecursiveDistinct($base[$key], $value);
                } else {
                    $base[$key] = $value;
                }
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * Merge environment-specific configuration files.
     *
     * Loads configuration files from an environment subdirectory and merges them
     * with the existing configuration.
     *
     * @param string $path The base configuration path
     * @param string|null $environment The environment name (subdirectory)
     * @return void
     */
    public function mergeEnvironment(string $path, ?string $environment): void
    {
        if ($environment !== null) {
            $envPath = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $environment;
            if (is_dir($envPath)) {
                $this->loadConfig($envPath, true);
            }
        }
    }

    /**
     * Set a configuration value using dot notation.
     *
     * Creates nested arrays as needed to accommodate the key path.
     * Example: set('database.host', 'localhost')
     *
     * @param string $key The configuration key in dot notation
     * @param mixed $value The value to set
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $segment) {
            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                $config[$segment] = [];
            }
            $config = &$config[$segment];
        }

        $config = $value;
    }

    /**
     * Get a configuration value using dot notation.
     *
     * Retrieves a value from the configuration using dot notation to traverse
     * nested arrays. Returns a default value if provided and key not found,
     * otherwise throws an exception.
     *
     * @param string $key The configuration key in dot notation
     * @param mixed $default Optional default value if key not found
     * @return mixed The configuration value
     * @throws Exception If the key is not found and no default is provided
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $hasDefault = func_num_args() === 2;
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                if ($hasDefault) {
                    return $default;
                }

                throw new Exception("Config key '{$key}' not found.");
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Get a deep copy of all configuration data.
     *
     * Returns a complete copy of the configuration array to prevent
     * external modifications to the internal state.
     *
     * @return array<string, mixed> A copy of all configuration data
     */
    public function all(): array
    {
        return unserialize(serialize($this->config));
    }

    /**
     * Get a reference to the raw configuration array.
     *
     * WARNING: Returns the actual internal configuration array,
     * allowing direct modifications. Use with caution.
     *
     * @return array<string, mixed> The raw configuration array
     */
    public function getRaw(): array
    {
        return $this->config;
    }

    /**
     * Replace the entire configuration array.
     *
     * Completely replaces the current configuration with the provided array.
     *
     * @param array<string, mixed> $config The new configuration array
     * @return void
     */
    public function replace(array $config): void
    {
        $this->config = $config;
    }
}
