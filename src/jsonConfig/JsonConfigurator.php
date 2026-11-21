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

    private array $environments = [];

    private string $currentEnv;

    public function __construct(
        string $path = 'configs',
        ?array $environments = [],
        ?string $varName = 'APP_ENV',
        bool $shouldMerge = true
    ) {
        $this->currentEnv = getenv($varName) ?: 'production';

        // Default environment aliases
        $defaultEnvironments = [
            'dev'  => 'development',
            'prod' => 'production'
        ];

        // Merge additional environments provided by client
        $this->environments = array_replace(
            $defaultEnvironments,
            $environments ?? []
        );

        $this->populateEnvironments();
        $this->load($path, $shouldMerge);
    }

    private function load(string $path, bool $shouldMerge)
    {
        // Scan and load environment configurations
        $files = $this->scanDirectory($path);

        sort($files, SORT_STRING);

        foreach ($files as $file) {
            $this->loadFile($file, $shouldMerge);
        }
    }

    private function populateEnvironments(): void
    {
        $environments = [];

        foreach ($this->environments as $short => $long) {
            $environments[$short] = $short;
            $environments[$long] = $short;
        }

        $this->environments = $environments;
    }

    private function getAliases(string $environment): array
    {
        $env = $this->environments[$environment] ?? $environment;

        return array_keys($this->environments, $env, true);
    }

    private function scanDirectory(string $path): array
    {
        if (!is_dir($path)) {
            throw new RuntimeException(
                "Environment config directory not found: {$path}"
            );
        }

        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $path,
                FilesystemIterator::SKIP_DOTS
            )
        );

        $aliases = $this->getAliases($this->currentEnv);

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $filename = $file->getFilename();

            foreach ($aliases as $alias) {
                if (str_ends_with($filename, ".{$alias}.json")) {
                    $files[] = $file->getPathname();
                    break;
                }
            }
        }

        return $files;
    }

    private function loadFile(string $file, bool $shouldMerge = true): void
    {
        $filename = basename($file);

        $aliases = $this->getAliases($this->currentEnv);

        $suffix = '/\.(' . implode(
            '|',
            array_map(
                fn($alias) => preg_quote($alias, '/'),
                $aliases
            )
        ) . ')\.json$/';

        $name = preg_replace($suffix, '', $filename);

        $content = file_get_contents($file);

        if ($content === false) {
            throw new RuntimeException(
                "Unable to read configuration file: {$file}"
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

            if ($shouldMerge) {
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

    public function getEnvironment(): string
    {
        return $this->currentEnv;
    }

    public function getEnvironmentAliases(): array
    {
        return $this->getAliases($this->currentEnv);
    }
}
