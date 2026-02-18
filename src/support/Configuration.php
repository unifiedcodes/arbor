<?php

namespace Arbor\support;

trait Configuration
{
    protected array $options = [];

    /**
     * Initialize defaults with user overrides.
     */
    protected function applyDefaults(array $overrides = []): void
    {
        $this->options = $this->mergeDefaults($overrides);
    }

    /**
     * Classes must define their default configuration.
     */
    abstract protected function defaultOptions(): array;

    /**
     * Resolve defaults merged with overrides.
     */
    protected function mergeDefaults(array $overrides = []): array
    {
        return array_replace_recursive(
            $this->defaultOptions(),
            $overrides
        );
    }

    /**
     * Read a resolved option.
     */
    protected function option(string $key, mixed $fallback = null): mixed
    {
        return value_at($this->options, $key, $fallback);
    }

    /**
     * Get fully resolved options.
     */
    protected function options(): array
    {
        return $this->options;
    }
}
