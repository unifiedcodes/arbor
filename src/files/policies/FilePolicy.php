<?php

namespace Arbor\files\policies;


abstract class FilePolicy
{
    protected array $options;

    public function __construct(array $options = [])
    {
        $this->options = $this->mergeDefaults($options);
    }

    abstract protected function defaults(): array;

    protected function mergeDefaults(array $options): array
    {
        return array_replace_recursive(
            $this->defaults(),
            $options
        );
    }

    protected function option(string $key, mixed $default = null): mixed
    {
        return value_at($this->options, $key, $default);
    }
}
