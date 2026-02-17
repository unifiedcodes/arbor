<?php

namespace Arbor\files\utilities;


abstract class FilePolicy
{
    protected array $options;

    public function __construct(array $options = [])
    {
        $this->options = $this->mergeOptions($options);
    }

    public function withOptions(array $options): self
    {
        return new static($options);
    }

    abstract protected function defaults(): array;

    protected function mergeOptions(array $options): array
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
