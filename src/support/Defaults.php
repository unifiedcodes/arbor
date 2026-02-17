<?php

namespace Arbor\support;

trait Defaults
{
    protected array $options = [];

    /**
     * Initialize defaults with user overrides.
     */
    protected function applyDefaults(array $overrides = []): void
    {
        $this->options = $this->resolveDefaults($overrides);
    }

    /**
     * Classes must define their default configuration.
     */
    abstract protected function defaults(): array;

    /**
     * Resolve defaults merged with overrides.
     */
    protected function resolveDefaults(array $overrides = []): array
    {
        return array_replace_recursive(
            $this->defaults(),
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
