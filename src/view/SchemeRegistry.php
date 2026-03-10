<?php

namespace Arbor\view;


use RuntimeException;
use InvalidArgumentException;
use Arbor\support\path\Uri;


/**
 * Registry for managing URI schemes and resolving file paths and asset URLs.
 * Handles scheme registration, normalization, and resolution of views and assets.
 */
final class SchemeRegistry
{
    /** @var array<string, Scheme> Map of registered schemes. */
    private array $schemes = [];

    /**
     * Constructor for the SchemeRegistry.
     *
     * @param bool $verifyFiles Whether to verify that files exist when resolving paths (default: false).
     */
    public function __construct(
        private bool $verifyFiles = false
    ) {}


    /**
     * Registers a new URI scheme with a root directory and optional base URL.
     *
     * @param string $scheme The scheme name.
     * @param string $root The root directory path for the scheme.
     * @param string|null $baseUrl The optional base URL for asset resolution.
     * @throws RuntimeException If the scheme is already registered.
     */
    public function register(string $scheme, string $root, ?string $baseUrl = null): void
    {
        $scheme = $this->normalizeName($scheme);

        if (isset($this->schemes[$scheme])) {
            throw new RuntimeException("Scheme '{$scheme}' already registered.");
        }

        $this->schemes[$scheme] = new Scheme($scheme, $root, $baseUrl);
    }


    /**
     * Retrieves a registered scheme by name.
     *
     * @param string $scheme The scheme name.
     * @return Scheme The scheme instance.
     * @throws RuntimeException If the scheme is not found.
     */
    public function get(string $scheme): Scheme
    {
        $scheme = $this->normalizeName($scheme);

        if (!isset($this->schemes[$scheme])) {
            throw new RuntimeException("Scheme '{$scheme}' not found.");
        }

        return $this->schemes[$scheme];
    }


    /**
     * Checks if a scheme is registered.
     *
     * @param string $scheme The scheme name to check.
     * @return bool True if the scheme is registered, false otherwise.
     */
    public function has(string $scheme): bool
    {
        $scheme = $this->normalizeName($scheme);

        return isset($this->schemes[$scheme]);
    }


    /**
     * Normalizes a scheme name to lowercase and validates it.
     *
     * @param string $scheme The scheme name to normalize.
     * @return string The normalized scheme name.
     * @throws InvalidArgumentException If the scheme is invalid or empty.
     */
    private static function normalizeName(string $scheme): string
    {
        $scheme = strtolower(trim($scheme));

        if ($scheme === '' || str_contains($scheme, '://')) {
            throw new InvalidArgumentException('Invalid mount scheme');
        }

        return $scheme;
    }


    /**
     * Normalizes a URI string or Uri object, applying a default scheme if needed.
     *
     * @param string|Uri $uri The URI to normalize.
     * @param string|null $default The default scheme to apply if URI lacks one.
     * @return Uri The normalized Uri object.
     * @throws InvalidArgumentException If the URI is empty.
     * @throws RuntimeException If the URI lacks a scheme and no default is provided.
     */
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


    /**
     * Resolves a view URI to its file path.
     * Automatically appends .php extension if none is provided.
     *
     * @param string|Uri $uri The view URI to resolve.
     * @param string|null $default The default scheme to apply if URI lacks one.
     * @return string The absolute file path to the view.
     * @throws RuntimeException If the URI has no path or file not found (in verify mode).
     */
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

        $file = normalizeFilePath(joinPath($scheme->root(), $relative));

        if ($this->verifyFiles && !is_file($file)) {
            throw new RuntimeException(
                "View file not found: '{$file}'"
            );
        }

        return $file;
    }


    /**
     * Resolves an asset URI to its public URL or path.
     * Supports external URLs (http/https) and public schemes.
     *
     * @param string|Uri $uri The asset URI to resolve.
     * @param string|null $default The default scheme to apply if URI lacks one.
     * @return string The asset URL or path.
     * @throws RuntimeException If the scheme is not public or file not found (in verify mode).
     */
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
