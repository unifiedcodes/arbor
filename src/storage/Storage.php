<?php

namespace Arbor\storage;


use Arbor\storage\stores\StoreInterface;
use Arbor\storage\Registry;
use Arbor\storage\Path;
use Arbor\stream\StreamInterface;
use Arbor\stream\StreamFactory;


class Storage
{
    protected Registry $registry;


    public function __construct()
    {
        $this->registry = new Registry();
    }


    // registry methods
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

    public function uriFromString(string $uri): Uri
    {
        return Uri::fromString($uri);
    }


    public function uriFromParts(string $scheme, string $path, ?string $fileName = null): Uri
    {
        return Uri::fromParts($scheme, $path, $fileName);
    }


    public function absolutePath(string|Uri $uri): string
    {
        $uri = $this->normalizeUri($uri);
        return Path::absolutePath($this->scheme($uri), $uri->path());
    }


    public function publicUrl(string|Uri $uri): string
    {
        $uri = $this->normalizeUri($uri);
        return Path::publicUrl($this->scheme($uri), $uri->path());
    }


    protected function normalizeUri(string|Uri $uri): Uri
    {
        return $uri instanceof Uri
            ? $uri
            : Uri::fromString($uri);
    }


    public function store(string|Uri $uri): StoreInterface
    {
        $uri = $this->normalizeUri($uri);
        $scheme = $this->registry->resolve($uri->scheme());
        return $scheme->store();
    }


    public function scheme(string|Uri $uri): Scheme
    {
        $uri = $this->normalizeUri($uri);
        return $this->registry->resolve($uri->scheme());
    }


    public function getScheme(string $schemeName): Scheme
    {
        return $this->registry->resolve($schemeName);
    }


    protected function resolveIO(string|Uri $uri): array
    {
        $uri = $this->normalizeUri($uri);

        $scheme = $this->registry->resolve($uri->scheme());

        $store = $scheme->store();
        $absolutePath = Path::absolutePath($scheme, $uri->path());

        return [$store, $absolutePath];
    }


    // IO methods
    public function read(string|Uri $uri): StreamInterface
    {
        [$store, $absolutePath] = $this->resolveIO($uri);

        $stream = $store->read($absolutePath);

        return $stream;
    }


    public function write(string|Uri $uri, string $contents): void
    {
        [$store, $absolutePath] = $this->resolveIO($uri);

        $stream = StreamFactory::fromString($contents);

        $store->write($absolutePath, $stream);
    }


    public function append(string|Uri $uri, string $contents): void
    {
        [$store, $absolutePath] = $this->resolveIO($uri);

        $stream = StreamFactory::fromString($contents);

        $store->append($absolutePath, $stream);
    }


    public function delete(string|Uri $uri): void
    {
        [$store, $absolutePath] = $this->resolveIO($uri);

        $store->delete($absolutePath);
    }


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


    public function exists(string|Uri $uri): bool
    {
        [$store, $absolutePath] = $this->resolveIO($uri);

        return $store->exists($absolutePath);
    }


    public function stats(string|Uri $uri): array
    {
        [$store, $absolutePath] = $this->resolveIO($uri);

        return $store->stats($absolutePath);
    }
}
