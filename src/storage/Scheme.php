<?php

namespace Arbor\storage;


use Arbor\storage\stores\StoreInterface;


/**
 * Represents a registered storage scheme and its associated configuration.
 *
 * A scheme binds a name (e.g. "s3", "local") to a {@see StoreInterface} implementation,
 * along with optional metadata such as a root path prefix, a base URL for public access,
 * and a visibility flag.
 *
 * Instances are immutable; all properties are set at construction time.
 *
 * @package Arbor\storage
 */
final class Scheme
{
    /**
     * @param string              $name    The scheme identifier (e.g. "local", "s3").
     * @param StoreInterface      $store   The store implementation backing this scheme.
     * @param string              $root    An optional root path prefix prepended to all resolved paths.
     * @param string|null         $baseUrl An optional base URL used to construct public URLs for resources.
     * @param bool                $public  Whether resources under this scheme are publicly accessible.
     */
    public function __construct(
        private string $name,
        private StoreInterface $store,
        private string $root = '',
        private ?string $baseUrl = null,
        private bool $public = false
    ) {}


    /**
     * Returns the scheme identifier.
     *
     * @return string The scheme name (e.g. "local", "s3").
     */
    public function name(): string
    {
        return $this->name;
    }


    /**
     * Returns the store implementation associated with this scheme.
     *
     * @return StoreInterface
     */
    public function store(): StoreInterface
    {
        return $this->store;
    }


    /**
     * Indicates whether resources under this scheme are publicly accessible.
     *
     * @return bool True if the scheme is public, false otherwise.
     */
    public function isPublic(): bool
    {
        return $this->public;
    }


    /**
     * Returns the base URL used to construct public URLs for resources under this scheme.
     *
     * @return string|null The base URL, or null if no public URL is configured.
     */
    public function baseUrl(): ?string
    {
        return $this->baseUrl;
    }


    /**
     * Returns the root path prefix for this scheme.
     *
     * All relative paths are resolved against this root when performing I/O operations.
     *
     * @return string The root path, or an empty string if none is configured.
     */
    public function root(): string
    {
        return $this->root;
    }
}
