<?php

namespace Arbor\view;


use RuntimeException;


final class SchemeRegistry
{
    private array $schemes = [];


    public function register(Scheme $scheme): void
    {
        $name = $scheme->name();

        if (isset($this->schemes[$name])) {
            throw new RuntimeException("Scheme '{$name}' already registered.");
        }

        $this->schemes[$name] = $scheme;
    }


    public function get(string $name): Scheme
    {
        if (!isset($this->schemes[$name])) {
            throw new RuntimeException("Scheme '{$name}' not found.");
        }

        return $this->schemes[$name];
    }


    public function has(string $name): bool
    {
        return isset($this->schemes[$name]);
    }
}
