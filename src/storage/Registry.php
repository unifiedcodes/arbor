<?php

namespace Arbor\storage;


use Arbor\storage\stores\StoreInterface;
use InvalidArgumentException;
use Exception;


/**
 * Maintains the registry of named storage schemes.
 *
 * Schemes are registered by name and resolved on demand. Each name must be
 * unique within a registry instance. The registry normalises scheme names and
 * root paths at registration time to ensure consistent lookups.
 *
 * @package Arbor\storage
 */
class Registry
{
    /** @var array<string, Scheme> Map of normalised scheme names to their {@see Scheme} instances. */
    private array $schemes = [];


    /**
     * Registers a new storage scheme.
     *
     * The scheme name is normalised to lowercase and trimmed before registration.
     * The root path is stripped of surrounding slashes; empty or bare-slash roots
     * are stored as an empty string.
     *
     * @param string         $schemeName The scheme identifier to register (e.g. "local", "s3").
     * @param StoreInterface $store      The store implementation to associate with this scheme.
     * @param string         $root       An optional root path prefix for all paths under this scheme.
     * @param string|null    $baseUrl    An optional base URL for generating public URLs.
     * @param bool           $public     Whether resources under this scheme are publicly accessible.
     *
     * @throws InvalidArgumentException If the scheme name is empty or contains "://".
     * @throws Exception                If a scheme with the same name has already been registered.
     */
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


    /**
     * Resolves a scheme name to its registered {@see Scheme} instance.
     *
     * @param  string $schemeName The scheme identifier to look up.
     * @return Scheme The registered scheme.
     *
     * @throws Exception If no scheme with the given name has been registered.
     */
    public function resolve(string $schemeName): Scheme
    {
        if (!isset($this->schemes[$schemeName])) {
            throw new Exception("scheme: {$schemeName} not registered in storage");
        }

        return $this->schemes[$schemeName];
    }


    /**
     * Normalises a scheme name to a consistent, comparable format.
     *
     * Trims whitespace and converts to lowercase. Throws if the result is empty
     * or contains "://", which would indicate a full URI was passed by mistake.
     *
     * @param  string $scheme The raw scheme name to normalise.
     * @return string The normalised scheme name.
     *
     * @throws InvalidArgumentException If the scheme is empty or contains "://".
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
     * Normalises a root path by stripping surrounding slashes.
     *
     * Paths that are empty or consist solely of "/" are stored as an empty string,
     * indicating no root prefix. All other paths have their leading and trailing
     * slashes removed.
     *
     * @param  string $root The raw root path to normalise.
     * @return string The normalised root path, or an empty string if no root applies.
     */
    private static function normalizeRoot(string $root): string
    {
        $root = trim($root);

        if ($root === '' || $root === '/') {
            return '';
        }

        return trim($root, '/');
    }
}
