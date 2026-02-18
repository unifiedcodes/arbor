<?php

namespace Arbor\storage;


use Arbor\storage\namespace\NamespaceInterface;
use Arbor\storage\stores\StoreInterface;
use InvalidArgumentException;
use Exception;


class Registry
{
    private array $schemes = [];
    private array $namespaceEnums = [];


    public function registerNamespaceEnum(string $enumClass): void
    {
        if (! enum_exists($enumClass)) {
            throw new InvalidArgumentException("{$enumClass} is not an enum");
        }

        if (! is_subclass_of($enumClass, NamespaceInterface::class)) {
            throw new InvalidArgumentException(
                "{$enumClass} must implement NamespaceInterface"
            );
        }

        if (in_array($enumClass, $this->namespaceEnums, true)) {
            return;
        }

        $this->namespaceEnums[] = $enumClass;
    }


    public function hasNamespaceEnum(string $enumClass): bool
    {
        return in_array($enumClass, $this->namespaceEnums, true);
    }


    public function register(
        string $schemeName,
        StoreInterface $store,
        string $root = '',
        ?string $baseUrl = null,
        bool $public = false
    ) {
        $schemeName = $this->normalizeName($schemeName);
        $root = $this->normalizeRoot($root);

        $scheme = new Scheme(
            $schemeName,
            $store,
            $root,
            $baseUrl,
            $public
        );

        if (isset($this->schemes[$schemeName])) {
            throw new Exception("scheme: {$schemeName} already registered in storage");
        }

        $this->schemes[$schemeName] = $scheme;
    }


    public function resolve(string $schemeName): Scheme
    {
        if (!isset($this->schemes[$schemeName])) {
            throw new Exception("scheme: {$schemeName} not registered in storage");
        }

        return $this->schemes[$schemeName];
    }


    private static function normalizeName(string $scheme): string
    {
        $scheme = strtolower(trim($scheme));

        if ($scheme === '' || str_contains($scheme, '://')) {
            throw new InvalidArgumentException('Invalid mount scheme');
        }

        return $scheme;
    }


    private static function normalizeRoot(string $root): string
    {
        $root = trim($root);

        if ($root === '' || $root === '/') {
            return '';
        }

        return trim($root, '/');
    }
}
