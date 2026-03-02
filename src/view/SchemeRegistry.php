<?php

namespace Arbor\view;


use RuntimeException;
use InvalidArgumentException;
use Arbor\support\path\Uri;


final class SchemeRegistry
{
    private array $schemes = [];

    public function __construct(
        private bool $verifyFiles = false
    ) {}


    public function register(string $scheme, string $root, ?string $baseUrl = null): void
    {
        $scheme = $this->normalizeName($scheme);

        if (isset($this->schemes[$scheme])) {
            throw new RuntimeException("Scheme '{$scheme}' already registered.");
        }

        $this->schemes[$scheme] = new Scheme($scheme, $root, $baseUrl);
    }


    public function get(string $scheme): Scheme
    {
        $scheme = $this->normalizeName($scheme);

        if (!isset($this->schemes[$scheme])) {
            throw new RuntimeException("Scheme '{$scheme}' not found.");
        }

        return $this->schemes[$scheme];
    }


    public function has(string $scheme): bool
    {
        $scheme = $this->normalizeName($scheme);

        return isset($this->schemes[$scheme]);
    }


    private static function normalizeName(string $scheme): string
    {
        $scheme = strtolower(trim($scheme));

        if ($scheme === '' || str_contains($scheme, '://')) {
            throw new InvalidArgumentException('Invalid mount scheme');
        }

        return $scheme;
    }


    public function normalize(string|Uri $uri, ?string $default = null): Uri
    {
        if ($uri instanceof Uri) {
            return $uri;
        }

        $uri = trim($uri);

        if ($uri === '') {
            throw new InvalidArgumentException('URI cannot be empty.');
        }

        if (!str_contains($uri, '://')) {

            if ($default === null || $default === '') {
                throw new RuntimeException(
                    "Cannot resolve '{$uri}' without a default scheme."
                );
            }

            $uri = $default . '://' . $uri;
        }

        return Uri::fromString($uri);
    }


    public function resolveView(string|Uri $uri, ?string $default = null): string
    {
        $uri = $this->normalize($uri, $default);

        $scheme = $this->get($uri->scheme());

        $relative = ltrim($uri->path(), '/');

        if ($relative === '') {
            throw new RuntimeException(
                "View URI '{$uri}' does not contain a path."
            );
        }

        if (pathinfo($relative, PATHINFO_EXTENSION) === '') {
            $relative .= '.php';
        }

        $file = normalizeFilePath($scheme->root() . $relative);

        if ($this->verifyFiles && !is_file($file)) {
            throw new RuntimeException(
                "View file not found: '{$file}'"
            );
        }

        return $file;
    }


    public function resolveAsset(string|Uri $uri, ?string $default = null): string
    {
        $uri = $this->normalize($uri, $default);

        $schemeName = $uri->scheme();

        // allow external URLs
        if (in_array($schemeName, ['http', 'https'], true)) {
            return (string) $uri;
        }

        $scheme = $this->get($schemeName);

        if (!$scheme->isPublic()) {
            throw new RuntimeException(
                "Scheme '{$schemeName}' is not public and cannot be used for assets."
            );
        }

        $relative = ltrim($uri->path(), '/');

        if ($relative === '') {
            throw new RuntimeException(
                "Asset URI '{$uri}' does not contain a path."
            );
        }

        if ($this->verifyFiles) {
            $file = normalizeFilePath($scheme->root() . $relative);

            if (!is_file($file)) {
                throw new RuntimeException(
                    "Asset file not found: '{$file}'"
                );
            }
        }

        return rtrim($scheme->baseUrl(), '/') . '/' . $relative;
    }
}
