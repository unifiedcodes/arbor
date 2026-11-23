<?php

namespace Arbor\jsonConfig;

use RuntimeException;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

class JsonConfigurator
{
    public array $configs = [];

    public function __construct(
        private string $path = 'configs',
        private bool $shouldMerge = true,
        private ?string $suffix = null,
    ) {
        $this->load();
    }

    private function load()
    {
        // Scan and load environment configurations
        $files = $this->scanDirectory($this->path);

        if (empty($files)) {
            $suffix = $this->suffix !== null
                ? " matching suffix '{$this->suffix}'"
                : '';

            throw new RuntimeException(
                "No JSON configuration files found in '{$this->path}'{$suffix}."
            );
        }

        sort($files, SORT_STRING);

        foreach ($files as $file) {
            $this->loadFile($file);
        }
    }


    private function scanDirectory(string $path): array
    {
        if (!is_dir($path)) {
            throw new RuntimeException(
                "Configuration directory not found: {$path}"
            );
        }

        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $path,
                FilesystemIterator::SKIP_DOTS
            )
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $filename = $file->getFilename();

            // Only consider JSON files.
            if (!str_ends_with($filename, '.json')) {
                continue;
            }

            // Filter by suffix when one is provided.
            if (
                $this->suffix !== null &&
                !str_ends_with($filename, ".{$this->suffix}.json")
            ) {
                continue;
            }

            $files[] = $file->getPathname();
        }

        return $files;
    }

    private function loadFile(string $file): void
    {
        $content = file_get_contents($file);

        if ($content === false) {
            throw new RuntimeException(
                "Unable to read configuration file: {$file}"
            );
        }

        $filename = basename($file);

        // Remove .json and the optional suffix.
        $name = substr($filename, 0, -5);

        if ($this->suffix !== null) {
            $name = substr(
                $name,
                0,
                - (strlen($this->suffix) + 1)
            );
        }

        try {
            $config = json_decode(
                $content,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (!is_array($config)) {
                throw new RuntimeException(
                    "Configuration must contain a JSON object or array: {$file}"
                );
            }

            if ($this->shouldMerge) {
                $this->configs = array_replace_recursive(
                    $this->configs,
                    $config
                );
            } else {
                $this->configs[$name] = $config;
            }
        } catch (JsonException $e) {
            throw new RuntimeException(
                "Invalid JSON in {$file}: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    // accessor
    public function get(string $key, mixed $default = null): mixed
    {
        $hasDefault = func_num_args() === 2;

        $sentinel = '%sentinel%';

        $value = value_at($this->configs, $key, $sentinel);

        if ($value === $sentinel) {
            if ($hasDefault) {
                return $default;
            }

            throw new RuntimeException("JsonConfig key '{$key}' not found.");
        }

        return $value;
    }
}
