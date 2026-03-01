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
