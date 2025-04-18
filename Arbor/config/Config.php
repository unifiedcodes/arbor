<?php

namespace Arbor\config;

use Exception;

/**
 * Class Config
 *
 * Manages application configuration by loading configuration files from a specified
 * directory and an optional environment-specific subdirectory. Validates the content
 * of each file and provides access to configuration values using dot notation.
 *
 * @package Arbor\config
 */
class Config
{
    /**
     * The requested environment (e.g., 'development', 'production').
     *
     * @var string|null
     */
    protected ?string $environment;

    
    /**
     * Holds all configuration values.
     *
     * @var array
     */
    protected array $config = [];


    /**
     * Config constructor.
     *
     * Loads the base configuration from the specified directory and, if provided,
     * loads environment-specific overrides.
     *
     * @param string      $configPath  Path to the base configuration directory.
     * @param string|null $environment Optional environment name (e.g., 'development', 'production').
     *
     * @throws Exception If the configuration directory is not found or if file contents are invalid.
     */
    public function __construct(string $configPath = 'configs', ?string $environment = null)
    {
        $this->environment = $environment;

        // Load base configuration
        $this->loadConfig($configPath);

        // Load environment-specific configuration overrides if provided
        if ($environment !== null) {
            $envPath = rtrim($configPath, '/\\') . DIRECTORY_SEPARATOR . $environment;
            if (is_dir($envPath)) {
                $this->loadConfig($envPath, true);
            }
        }
    }

    /**
     * Loads configuration files from the given directory.
     *
     * This method reads all PHP files in the directory, expects each to return an array,
     * and either sets or merges the configuration data.
     *
     * @param string $configPath Path to the configuration directory.
     * @param bool   $merge      Whether to merge the loaded configuration with existing values.
     *
     * @return void
     *
     * @throws Exception If the directory does not exist or a file returns invalid content.
     */
    protected function loadConfig(string $configPath, bool $merge = false): void
    {
        if (!is_dir($configPath)) {
            throw new Exception("Config directory not found: $configPath");
        }

        foreach (glob($configPath . '/*.php') as $file) {
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
     * Recursively merges two arrays.
     *
     * Values from the overriding array will replace those in the base array.
     *
     * @param array $base     The base array.
     * @param array $override The overriding array.
     *
     * @return array The merged array.
     */
    protected function arrayMergeRecursiveDistinct(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (array_key_exists($key, $base) && is_array($base[$key]) && is_array($value)) {
                $base[$key] = $this->arrayMergeRecursiveDistinct($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }

    /**
     * Retrieves a configuration value using dot notation.
     *
     * Example: get('database.host') returns the host value from the database config.
     *
     * @param string $key     The configuration key in dot notation.
     * @param mixed  $default The default value to return if the key is not found.
     *
     * @return mixed The configuration value or the default if the key is not found.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Sets a configuration value using dot notation.
     *
     * Example: set('database.port', 3306) sets the port value in the database config.
     *
     * @param string $key   The configuration key in dot notation.
     * @param mixed  $value The value to set.
     *
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
     * Retrieves all configuration values as an immutable array.
     *
     * @return array A deep copy of the entire configuration array.
     */
    public function all(): array
    {
        return unserialize(serialize($this->config));
    }
}
