<?php

namespace Arbor\support\path;


use RuntimeException;


final class PathNormalizer
{
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
    public static function relativePath(string $path): string
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
