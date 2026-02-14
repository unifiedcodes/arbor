<?php

namespace Arbor\storage;


use Arbor\storage\stores\StoreInterface;
use InvalidArgumentException;


final class Mount
{
    private string $scheme;
    private string $root;
    private StoreInterface $store;
    private ?string $baseUrl;
    private bool $public;


    public function __construct(
        string $scheme,
        StoreInterface $store,
        string $root = '',
        ?string $baseUrl = null,
        bool $public = false
    ) {
        $this->scheme  = self::normalizeScheme($scheme);
        $this->store   = $store;
        $this->root    = self::normalizeRoot($root);
        $this->baseUrl = $baseUrl ? rtrim($baseUrl, '/') : null;
        $this->public  = $public;
    }


    public function scheme(): string
    {
        return $this->scheme;
    }


    public function store(): StoreInterface
    {
        return $this->store;
    }


    public function isPublic(): bool
    {
        return $this->public;
    }


    public function baseUrl(): ?string
    {
        return $this->baseUrl;
    }


    private static function normalizeScheme(string $scheme): string
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
