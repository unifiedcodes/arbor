<?php

namespace Arbor\storage;


use Arbor\storage\stores\StoreInterface;
use Arbor\storage\Registry;
use Arbor\storage\Path;
use Arbor\stream\StreamInterface;
use Arbor\stream\StreamFactory;


/**
 * Central entry point for all storage operations.
 *
 * Manages a registry of named schemes (e.g. "local", "s3", "gcs"), each backed
 * by a {@see StoreInterface} implementation. Provides a unified API for URI
 * construction, path resolution, and file I/O across any registered store.
 *
 * @package Arbor\storage
 */
class Storage
{
    /** @var Registry The scheme registry that maps scheme names to {@see Scheme} instances. */
    protected Registry $registry;


    /**
     * Initialises the Storage instance with an empty scheme registry.
     */
    public function __construct()
    {
        $this->registry = new Registry();
    }


    // registry methods

    /**
     * Registers a new storage scheme with its backing store and optional configuration.
     *
     * @param string         $schemeName The scheme identifier (e.g. "local", "s3").
     * @param StoreInterface $store      The store implementation to associate with this scheme.
     * @param string         $root       An optional root path prefix applied to all paths under this scheme.
     * @param string|null    $baseUrl    An optional base URL used to generate public URLs for this scheme.
     * @param bool           $public     Whether files under this scheme are publicly accessible.
     */
    public function addScheme(
        string $schemeName,
        StoreInterface $store,
        string $root = '',
        ?string $baseUrl = null,
        bool $public = false
    ): void {
        $this->registry->register(
            $schemeName,
            $store,
            $root,
            $baseUrl,
            $public
        );
    }


    // URI, URL & Path methods.

    /**
     * Creates a {@see Uri} instance by parsing a full URI string.
     *
     * @param  string $uri A full URI string, e.g. "s3://bucket/key".
     * @return Uri
     *
     * @throws \InvalidArgumentException If the URI string is malformed or the scheme is invalid.
     */
    public function uriFromString(string $uri): Uri
    {
        return Uri::fromString($uri);
    }


    /**
     * Creates a {@see Uri} instance from discrete scheme, path, and optional filename parts.
     *
     * @param  string      $scheme   A valid URI scheme.
     * @param  string      $path     The directory path component.
     * @param  string|null $fileName Optional filename to append to the path.
     * @return Uri
     *
     * @throws \InvalidArgumentException If the scheme or filename is invalid.
     */
    public function uriFromParts(string $scheme, string $path, ?string $fileName = null): Uri
    {
        return Uri::fromParts($scheme, $path, $fileName);
    }


    /**
     * Resolves the absolute filesystem or storage path for the given URI.
     *
     * Combines the scheme's configured root with the URI's relative path.
     *
     * @param  string|Uri $uri A URI string or {@see Uri} instance.
     * @return string The fully resolved absolute path.
     */
    public function absolutePath(string|Uri $uri): string
    {
        $uri = $this->normalizeUri($uri);
        return Path::absolutePath($this->scheme($uri), $uri->path());
    }


    /**
     * Resolves the public URL for the given URI.
     *
     * Uses the scheme's configured base URL to construct a publicly accessible URL.
     *
     * @param  string|Uri $uri A URI string or {@see Uri} instance.
     * @return string The public URL for the resource.
     */
    public function publicUrl(string|Uri $uri): string
    {
        $uri = $this->normalizeUri($uri);
        return Path::publicUrl($this->scheme($uri), $uri->path());
    }


    /**
     * Ensures the given value is a {@see Uri} instance.
     *
     * Passes through an existing Uri unchanged; parses a string URI via {@see Uri::fromString()}.
     *
     * @param  string|Uri $uri A URI string or {@see Uri} instance.
     * @return Uri
     */
    protected function normalizeUri(string|Uri $uri): Uri
    {
        return $uri instanceof Uri
            ? $uri
            : Uri::fromString($uri);
    }


    /**
     * Returns the {@see StoreInterface} backing the scheme of the given URI.
     *
     * @param  string|Uri $uri A URI string or {@see Uri} instance.
     * @return StoreInterface
     */
    public function store(string|Uri $uri): StoreInterface
    {
        $uri = $this->normalizeUri($uri);
        $scheme = $this->registry->resolve($uri->scheme());
        return $scheme->store();
    }


    /**
     * Returns the {@see Scheme} registered for the scheme component of the given URI.
     *
     * @param  string|Uri $uri A URI string or {@see Uri} instance.
     * @return Scheme
     */
    public function scheme(string|Uri $uri): Scheme
    {
        $uri = $this->normalizeUri($uri);
        return $this->registry->resolve($uri->scheme());
    }


    /**
     * Returns the {@see Scheme} registered under the given scheme name.
     *
     * @param  string $schemeName The scheme identifier to look up.
     * @return Scheme
     */
    public function getScheme(string $schemeName): Scheme
    {
        return $this->registry->resolve($schemeName);
    }


    /**
     * Resolves a URI to its backing store and absolute path.
     *
     * Used internally as a shared pre-step for all I/O operations.
     *
     * @param  string|Uri $uri A URI string or {@see Uri} instance.
     * @return array{0: StoreInterface, 1: string} A tuple of [store, absolutePath].
     */
    protected function resolveIO(string|Uri $uri): array
    {
        $uri = $this->normalizeUri($uri);

        $scheme = $this->registry->resolve($uri->scheme());

        $store = $scheme->store();
        $absolutePath = Path::absolutePath($scheme, $uri->path());

        return [$store, $absolutePath];
    }


    // IO methods

    /**
     * Reads the contents of the file at the given URI as a stream.
     *
     * @param  string|Uri $uri A URI string or {@see Uri} instance.
     * @return StreamInterface A readable stream of the file's contents.
     */
    public function read(string|Uri $uri): StreamInterface
    {
        [$store, $absolutePath] = $this->resolveIO($uri);

        $stream = $store->read($absolutePath);

        return $stream;
    }


    /**
     * Writes (creates or overwrites) a file at the given URI with the provided contents.
     *
     * @param  string|Uri $uri      A URI string or {@see Uri} instance.
     * @param  string     $contents The string content to write.
     */
    public function write(string|Uri $uri, string $contents): void
    {
        [$store, $absolutePath] = $this->resolveIO($uri);

        $stream = StreamFactory::fromString($contents);

        $store->write($absolutePath, $stream);
    }


    /**
     * Appends content to an existing file at the given URI.
     *
     * @param  string|Uri $uri      A URI string or {@see Uri} instance.
     * @param  string     $contents The string content to append.
     */
    public function append(string|Uri $uri, string $contents): void
    {
        [$store, $absolutePath] = $this->resolveIO($uri);

        $stream = StreamFactory::fromString($contents);

        $store->append($absolutePath, $stream);
    }


    /**
     * Deletes the file at the given URI.
     *
     * @param string|Uri $uri A URI string or {@see Uri} instance.
     */
    public function delete(string|Uri $uri): void
    {
        [$store, $absolutePath] = $this->resolveIO($uri);

        $store->delete($absolutePath);
    }


    /**
     * Copies a file from the source URI to the destination URI.
     *
     * If both URIs resolve to the same store instance, the copy is delegated to
     * the store's native copy method. Otherwise, a cross-store read-then-write is
     * performed, and the stream is closed in a finally block to ensure cleanup.
     *
     * @param string|Uri $source      The source URI.
     * @param string|Uri $destination The destination URI.
     */
    public function copy(string|Uri $source, string|Uri $destination): void
    {
        [$sourceStore, $sourcePath] = $this->resolveIO($source);
        [$destinationStore, $destinationPath] = $this->resolveIO($destination);

        // Same store → delegate to store's copy method.
        if ($sourceStore === $destinationStore) {
            $sourceStore->copy($sourcePath, $destinationPath);
        }

        // Cross-store → read+write
        $stream = $sourceStore->read($sourcePath);

        try {
            $destinationStore->write($destinationPath, $stream);
        } finally {
            $stream->close();
        }
    }


    /**
     * Moves a file from the source URI to the destination URI.
     *
     * If both URIs resolve to the same store instance, a native rename is used.
     * Otherwise, a cross-store read-then-write is performed, followed by deletion
     * of the source file. The stream is closed in a finally block to ensure cleanup.
     *
     * @param string|Uri $source      The source URI.
     * @param string|Uri $destination The destination URI.
     */
    public function move(string|Uri $source, string|Uri $destination): void
    {
        [$sourceStore, $sourcePath] = $this->resolveIO($source);
        [$destinationStore, $destinationPath] = $this->resolveIO($destination);

        // Same store → rename
        if ($sourceStore === $destinationStore) {
            $sourceStore->rename($sourcePath, $destinationPath);
        }

        // Cross-store → copy then delete
        $stream = $sourceStore->read($sourcePath);

        try {
            $destinationStore->write($destinationPath, $stream);
        } finally {
            $stream->close();
        }

        $sourceStore->delete($sourcePath);
    }


    /**
     * Checks whether a file exists at the given URI.
     *
     * @param  string|Uri $uri A URI string or {@see Uri} instance.
     * @return bool True if the file exists, false otherwise.
     */
    public function exists(string|Uri $uri): bool
    {
        [$store, $absolutePath] = $this->resolveIO($uri);

        return $store->exists($absolutePath);
    }


    /**
     * Returns metadata/statistics for the file at the given URI.
     *
     * The shape of the returned array is determined by the underlying store implementation.
     *
     * @param  string|Uri $uri A URI string or {@see Uri} instance.
     * @return array File metadata (e.g. size, mime type, last modified timestamp).
     */
    public function stats(string|Uri $uri): array
    {
        [$store, $absolutePath] = $this->resolveIO($uri);

        return $store->stats($absolutePath);
    }
}
