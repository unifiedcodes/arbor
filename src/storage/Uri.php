<?php

namespace Arbor\storage;


use InvalidArgumentException;


/**
 * Represents an immutable URI consisting of a scheme and a path.
 *
 * URIs follow the format: `scheme://path`
 *
 * This class enforces RFC 3986-compliant scheme validation and normalised
 * relative path semantics. All mutating methods return a new instance,
 * preserving immutability.
 *
 * @package Arbor\storage
 */
final class Uri
{
    /** @var string The URI scheme (e.g. "s3", "local", "gcs"). Always lowercase. */
    private string $scheme;

    /** @var string The normalised relative path component of the URI. */
    private string $path;


    /**
     * Private constructor. Use the static factory methods to create instances.
     *
     * @param string $scheme A validated, lowercased URI scheme.
     * @param string $path   A normalised relative path.
     */
    private function __construct(string $scheme, string $path)
    {
        $this->scheme = $scheme;
        $this->path   = $path;
    }


    /**
     * Creates a Uri instance by parsing a full URI string.
     *
     * The string must contain "://" as a scheme delimiter.
     * The scheme is validated against RFC 3986 rules and lowercased.
     * The path is normalised via {@see Path::normalizeRelativePath()}.
     *
     * @param  string $uri The full URI string, e.g. "s3://bucket/key".
     * @return self
     *
     * @throws InvalidArgumentException If the URI does not contain "://" or the scheme is invalid.
     */
    public static function fromString(string $uri): self
    {
        if (!str_contains($uri, '://')) {
            throw new InvalidArgumentException("Invalid URI: {$uri}");
        }

        [$scheme, $path] = explode('://', $uri, 2);

        $scheme = self::assertValidScheme($scheme);

        $path = Path::normalizeRelativePath($path);

        return new self(
            strtolower($scheme),
            $path
        );
    }


    /**
     * Creates a Uri instance from discrete scheme, path, and optional filename parts.
     *
     * The path is sanitised to enforce relative semantics (leading slashes stripped,
     * backslashes normalised). If a filename is provided it is appended to the path
     * after validation, preventing directory-separator injection.
     *
     * @param  string      $scheme   A valid URI scheme.
     * @param  string      $path     The directory path component.
     * @param  string|null $fileName Optional filename to append to the path.
     * @return self
     *
     * @throws InvalidArgumentException If the scheme or filename is invalid.
     */
    public static function fromParts(string $scheme, string $path, ?string $fileName = null): self
    {
        $scheme = self::assertValidScheme($scheme);

        // normalizing path formatting.
        $path = self::sanatizePath($path);

        // using filename if provided.
        if ($fileName !== null) {
            $fileName = self::assertValidFileName($fileName);
            $path = rtrim($path, '/') . '/' . $fileName;
        }

        return new self(
            $scheme,
            Path::normalizeRelativePath($path)
        );
    }


    /**
     * Returns the URI scheme component.
     *
     * @return string The lowercased scheme (e.g. "s3", "local").
     */
    public function scheme(): string
    {
        return $this->scheme;
    }


    /**
     * Returns the URI path component.
     *
     * @return string The normalised relative path.
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Returns a new Uri with the given path, preserving the current scheme.
     *
     * The provided path is sanitised and normalised before being applied.
     *
     * @param  string $path The new path to use.
     * @return self   A new Uri instance with the replaced path.
     */
    public function withPath(string $path): self
    {
        $path = self::sanatizePath($path);

        return new self(
            $this->scheme,
            Path::normalizeRelativePath($path)
        );
    }


    /**
     * Returns a new Uri with the given filename appended to the current path.
     *
     * The filename must not contain directory separators. The existing path is
     * used as the base directory, with the filename joined by a "/".
     *
     * @param  string $fileName The filename to append (must not contain "/" or "\").
     * @return self   A new Uri instance with the filename appended to the path.
     *
     * @throws InvalidArgumentException If the filename is empty or contains directory separators.
     */
    public function withFileName(string $fileName): self
    {
        $fileName = self::assertValidFileName($fileName);

        $basePath = rtrim($this->path, '/') . '/';

        return new self(
            $this->scheme,
            Path::normalizeRelativePath($basePath . $fileName)
        );
    }


    /**
     * Validates and sanitises a filename.
     *
     * Ensures the filename is non-empty and contains no directory separators
     * ("/" or "\") to prevent path injection attacks.
     *
     * @param  string $fileName The raw filename to validate.
     * @return string The trimmed, validated filename.
     *
     * @throws InvalidArgumentException If the filename is empty or contains directory separators.
     */
    private static function assertValidFileName(string $fileName): string
    {
        $fileName = trim($fileName);

        if ($fileName === '') {
            throw new InvalidArgumentException('Filename cannot be empty');
        }

        // Disallow path injection
        if (str_contains($fileName, '/') || str_contains($fileName, '\\')) {
            throw new InvalidArgumentException('Filename must not contain directory separators');
        }

        return $fileName;
    }


    /**
     * Validates and normalises a URI scheme.
     *
     * Schemes must be non-empty, must not contain "://" and must match the
     * RFC 3986 pattern: a letter followed by any combination of letters, digits,
     * "+", "-", or ".". The scheme is returned in lowercase.
     *
     * @param  string $scheme The raw scheme string to validate.
     * @return string The trimmed, lowercased, validated scheme.
     *
     * @throws InvalidArgumentException If the scheme is empty, contains "://" or fails RFC 3986 validation.
     */
    private static function assertValidScheme(string $scheme): string
    {
        $scheme = trim($scheme);

        if ($scheme === '') {
            throw new InvalidArgumentException('Scheme cannot be empty');
        }

        if (str_contains($scheme, '://')) {
            throw new InvalidArgumentException('Scheme must not contain "://"');
        }

        // RFC 3986 compliant scheme validation
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*$/', $scheme)) {
            throw new InvalidArgumentException(
                "Invalid scheme format: {$scheme}"
            );
        }

        return strtolower($scheme);
    }


    /**
     * Returns the full URI as a string in the format "scheme://path".
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->scheme . '://' . $this->path;
    }


    /**
     * Returns the full URI as a string. Alias for {@see __toString()}.
     *
     * @return string
     */
    public function toString(): string
    {
        return (string) $this;
    }


    /**
     * Sanitises a raw path string to enforce relative path semantics.
     *
     * Performs the following normalisations in order:
     * - Trims surrounding whitespace.
     * - Converts Windows-style backslashes to forward slashes.
     * - Strips leading slashes to ensure the path is relative.
     *
     * @param  string $path The raw path to sanitise.
     * @return string The sanitised relative path.
     */
    private static function sanatizePath(string $path): string
    {
        // Trim surrounding whitespace
        $path = trim($path);

        // Normalize Windows separators early
        $path = str_replace('\\', '/', $path);

        // Remove leading slashes to enforce relative semantics
        $path = ltrim($path, '/');

        return $path;
    }
}
