<?php

namespace Arbor\view;


use InvalidArgumentException;


final class Scheme
{
    public function __construct(
        private string $name,
        private string $root,
        private ?string $baseUrl = null
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('Scheme name cannot be empty.');
        }

        $root = trim($root);

        if ($root === '' || $root === '/') {
            return '';
        }

        $this->root = trim($root, '/');
    }

    public function name(): string
    {
        return $this->name;
    }

    public function root(): string
    {
        return $this->root;
    }

    public function baseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function isPublic(): bool
    {
        return !empty($this->baseUrl);
    }
}
