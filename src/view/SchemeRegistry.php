<?php

namespace Arbor\view;


use RuntimeException;
use InvalidArgumentException;


final class SchemeRegistry
{
    private array $schemes = [];


    public function register(string $name, string $root, ?string $baseUrl = null): void
    {
        $this->normalizeName($name);

        if (isset($this->schemes[$name])) {
            throw new RuntimeException("Scheme '{$name}' already registered.");
        }

        $this->schemes[$name] = new Scheme($name, $root, $baseUrl);
    }


    public function get(string $name): Scheme
    {
        $this->normalizeName($name);

        if (!isset($this->schemes[$name])) {
            throw new RuntimeException("Scheme '{$name}' not found.");
        }

        return $this->schemes[$name];
    }


    public function has(string $name): bool
    {
        $this->normalizeName($name);

        return isset($this->schemes[$name]);
    }


    private static function normalizeName(string $scheme): string
    {
        $scheme = strtolower(trim($scheme));

        if ($scheme === '' || str_contains($scheme, '://')) {
            throw new InvalidArgumentException('Invalid mount scheme');
        }

        return $scheme;
    }
}
