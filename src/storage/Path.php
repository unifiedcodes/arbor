<?php

namespace Arbor\storage;


use Arbor\storage\Scheme;
use InvalidArgumentException;
use RuntimeException;


class Path
{
    public static function parseUri(string $uri): array
    {
        if (!str_contains($uri, '://')) {
            throw new InvalidArgumentException("Invalid URI: {$uri}");
        }

        [$scheme, $path] = explode('://', $uri, 2);

        if ($scheme === '') {
            throw new InvalidArgumentException("URI scheme missing: {$uri}");
        }

        $path = self::normalizeRelativePath($path);

        return [
            'scheme' => $scheme,
            'path'   => $path
        ];
    }


    public static function uri(Scheme $scheme, string $relativePath): string
    {
        return $scheme->name() . '://' . ltrim($relativePath, '/');
    }


    public static function absolutePath(Scheme $scheme, string $relativePath): string
    {
        $relativePath = self::normalizeRelativePath($relativePath);

        $root = $scheme->root();

        if ($root === '') {
            return $relativePath;
        }

        return rtrim($root, '/') . '/' . $relativePath;
    }


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
