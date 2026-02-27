<?php

namespace Arbor\storage;


use Arbor\storage\Scheme;
use Arbor\support\path\PathNormalizer;
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
        $relativePath = PathNormalizer::relativePath($relativePath);

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
}
