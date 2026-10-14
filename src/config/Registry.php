<?php

namespace Arbor\config;

use Arbor\support\Macros;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

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


    public function loadDir(string $dir): static
    {
        $this->config = $this->loadDirItems($dir);

        return $this;
    }

    public function mergeDir(string $dir): static
    {
        $items = $this->loadDirItems($dir);

        $this->config = array_replace_recursive(
            $this->config,
            $items
        );

        return $this;
    }

    /**
     * Load PHP configuration files from a directory.
     */
    protected function loadDirItems(string $dir): array
    {
        $directory = rtrim($dir, DIRECTORY_SEPARATOR);

        if (!is_dir($directory)) {
            throw new RuntimeException(
                "Configuration directory does not exist: {$directory}"
            );
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $directory,
                FilesystemIterator::SKIP_DOTS
            )
        );

        $items = [];

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = substr(
                $file->getPathname(),
                strlen($directory) + 1
            );

            $segments = explode(
                DIRECTORY_SEPARATOR,
                $relativePath
            );

            $last = count($segments) - 1;

            $segments[$last] = pathinfo(
                $segments[$last],
                PATHINFO_FILENAME
            );

            $value = require $file->getPathname();

            if (!is_array($value)) {
                throw new RuntimeException(
                    "Configuration file must return an array: "
                        . $file->getPathname()
                );
            }

            $this->setNestedValue($items, $segments, $value);
        }

        return $items;
    }

    /**
     * Set a value using an array of path segments.
     */
    protected function setNestedValue(
        array &$items,
        array $segments,
        mixed $value
    ): void {
        $key = array_shift($segments);

        if ($key === null) {
            return;
        }

        if ($segments === []) {
            $items[$key] = $value;
            return;
        }

        if (!isset($items[$key])) {
            $items[$key] = [];
        }

        if (!is_array($items[$key])) {
            throw new RuntimeException(
                "Configuration path conflict at: {$key}"
            );
        }

        $this->setNestedValue($items[$key], $segments, $value);
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
        value_set_at($this->config, $key, $value);
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

        $sentinel = '%sentinel%';

        $value = value_at($this->config, $key, $sentinel);

        if ($value === $sentinel) {
            if ($hasDefault) {
                return $default;
            }
            throw new Exception("Config key '{$key}' not found.");
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
