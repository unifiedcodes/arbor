<?php

namespace Arbor\storage;


use Arbor\storage\Scheme;
use RuntimeException;


/**
 * Provides static utilities for path and URL resolution within the storage system.
 *
 * Handles construction of absolute storage paths, public URLs, and normalisation
 * of relative paths. All path normalisation enforces security constraints including
 * null byte rejection, traversal prevention, absolute path blocking, and filtering
 * of reserved Windows device names.
 *
 * @package Arbor\storage
 */
class Path
{
    /**
     * Resolves an absolute storage path by combining a scheme's root with a relative path.
     *
     * If the scheme has no configured root, the normalised relative path is returned as-is.
     * Otherwise, the root and relative path are joined with a single "/".
     *
     * @param  Scheme $scheme       The scheme whose root prefix will be applied.
     * @param  string $relativePath The relative path to resolve.
     * @return string The absolute storage path.
     *
     * @throws RuntimeException If the relative path fails normalisation.
     */
    public static function absolutePath(Scheme $scheme, string $relativePath): string
    {
        $relativePath = self::normalizeRelativePath($relativePath);

        $root = $scheme->root();

        if ($root === '') {
            return $relativePath;
        }

        return rtrim($root, '/') . '/' . $relativePath;
    }


    /**
     * Constructs a public URL for a resource by combining the scheme's base URL with a relative path.
     *
     * The scheme must be marked as public and must have a configured base URL. Trailing
     * slashes on the base URL and leading slashes on the path are normalised before joining.
     *
     * @param  Scheme $scheme       The scheme to use for URL construction.
     * @param  string $relativePath The relative path of the resource.
     * @return string The full public URL.
     *
     * @throws RuntimeException If the scheme is not public or has no base URL configured.
     */
    public static function publicUrl(Scheme $scheme, string $relativePath): string
    {
        if (!$scheme->isPublic()) {
            throw new RuntimeException(
                "Scheme '{$scheme->name()}' is not public"
            );
        }

        if ($scheme->baseUrl() === null) {
            throw new RuntimeException(
                "Scheme '{$scheme->name()}' has no base URL"
            );
        }

        return rtrim($scheme->baseUrl(), '/')
            . '/'
            . ltrim($relativePath, '/');
    }


    /**
     * Normalises a relative path and validates it against a set of security constraints.
     *
     * The following rules are enforced in order:
     *
     * 1. Null bytes are rejected outright, as they are a universal exploit vector.
     * 2. Backslashes are converted to forward slashes for cross-platform consistency.
     * 3. Absolute paths are blocked — Unix-style leading slashes, Windows drive letters
     *    (e.g. "C:/"), and UNC paths ("//") are all rejected.
     * 4. Each path segment is checked for traversal attempts ("..").
     * 5. The base filename of each segment is checked against a list of reserved
     *    Windows device names (e.g. "CON", "NUL", "COM1") to prevent device file access.
     * 6. Empty segments and current-directory references (".") are silently discarded.
     *
     * The resulting path is returned as a clean, slash-separated relative string
     * with no leading or trailing slashes.
     *
     * @param  string $path The raw path to normalise.
     * @return string The sanitised, normalised relative path.
     *
     * @throws RuntimeException If the path contains a null byte, is absolute, contains
     *                          a traversal segment, or uses a reserved Windows device name.
     */
    public static function normalizeRelativePath(string $path): string
    {
        // 1. Null byte — universal exploit
        if (str_contains($path, "\0")) {
            throw new RuntimeException("Invalid path");
        }

        // 2. Normalize separators (Windows + Unix)
        $path = str_replace('\\', '/', $path);

        // 3. Block absolute paths (Unix, Windows drive letters, UNC)
        if (
            str_starts_with($path, '/') ||
            str_starts_with($path, '//') ||
            preg_match('#^[A-Za-z]:/#', $path)
        ) {
            throw new RuntimeException("Absolute paths not allowed");
        }

        // 4. Windows reserved device names (base name only)
        static $reserved = [
            'con',
            'prn',
            'aux',
            'nul',
            'com1',
            'com2',
            'com3',
            'com4',
            'com5',
            'com6',
            'com7',
            'com8',
            'com9',
            'lpt1',
            'lpt2',
            'lpt3',
            'lpt4',
            'lpt5',
            'lpt6',
            'lpt7',
            'lpt8',
            'lpt9'
        ];

        $parts = explode('/', trim($path, '/'));
        $clean = [];

        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            // Real traversal
            if ($part === '..') {
                throw new RuntimeException("Path traversal detected");
            }

            // Extract filename without extension
            $base = strtolower(pathinfo($part, PATHINFO_FILENAME));

            if (in_array($base, $reserved, true)) {
                throw new RuntimeException("Reserved Windows filename");
            }

            $clean[] = $part;
        }

        return implode('/', $clean);
    }
}
