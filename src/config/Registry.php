<?php

namespace Arbor\config;


use Exception;


class Registry
{
    protected array $config = [];


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


    public function mergeEnvironment($path, $environment)
    {
        if ($environment !== null) {
            $envPath = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $environment;
            if (is_dir($envPath)) {
                $this->loadConfig($envPath, true);
            }
        }
    }


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


    public function all(): array
    {
        return unserialize(serialize($this->config));
    }


    public function getRaw(): array
    {
        return $this->config;
    }


    public function replace(array $config): void
    {
        $this->config = $config;
    }
}
