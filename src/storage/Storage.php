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
    ) {
        $this->registry->register(
            $schemeName,
            $store,
            $root,
            $baseUrl,
            $public
        );
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


    public function scheme(string $schemeName): Scheme
    {
        return $this->registry->resolve($schemeName);
    }


    // path methods
    public function url(string|Uri $uri): string
    {
        $uri = $this->normalizeUri($uri);

        $scheme = $this->registry->resolve($uri->scheme());

        return Path::publicUrl($scheme, $uri->path());
    }


    // IO methods
    public function read(string|Uri $uri): StreamInterface
    {
        $uri = $this->normalizeUri($uri);
        $scheme = $this->registry->resolve($uri->scheme());

        $absolutePath = Path::absolutePath($scheme, $uri->path());

        $stream = $scheme->store()->read($absolutePath);

        return $stream;
    }


    public function write(string|Uri $uri, string $contents): bool
    {
        $uri = $this->normalizeUri($uri);
        $scheme = $this->registry->resolve($uri->scheme());

        $absolutePath = Path::absolutePath($scheme, $uri->path());

        $stream = StreamFactory::fromString($contents);

        $scheme->store()->write($absolutePath, $stream);

        return true;
    }


    public function append(string|Uri $uri, string $contents): bool
    {
        $uri = $this->normalizeUri($uri);
        $scheme = $this->registry->resolve($uri->scheme());

        $absolutePath = Path::absolutePath($scheme, $uri->path());

        if (!$scheme->store()->exists($absolutePath)) {
            return $this->write($uri, $contents);
        }

        $existing = $scheme->store()->read($absolutePath);
        $out = StreamFactory::empty();

        try {
            while (!$existing->eof()) {
                $out->write($existing->read(8192));
            }
        } finally {
            $existing->close();
        }

        $out->write($contents);

        $scheme->store()->write($absolutePath, $out);

        return true;
    }


    public function delete(string|Uri $uri): bool
    {
        $uri = $this->normalizeUri($uri);
        $scheme = $this->registry->resolve($uri->scheme());

        $absolutePath = Path::absolutePath($scheme, $uri->path());

        $scheme->store()->delete($absolutePath);

        return true;
    }


    public function copy(string|Uri $source, string $destination): bool
    {
        $source = $this->normalizeUri($source);
        $destination   = $this->normalizeUri($destination);

        $sourceScheme = $this->registry->resolve($source->scheme());
        $destScheme   = $this->registry->resolve($destination->scheme());

        $sourcePath = Path::absolutePath($sourceScheme, $source->path());
        $destPath   = Path::absolutePath($destScheme, $destination->path());


        $stream = $sourceScheme->store()->read($sourcePath);
        try {
            $destScheme->store()->write($destPath, $stream);
        } finally {
            $stream->close();
        }

        return true;
    }


    public function move(string|Uri $source, string $destination): bool
    {
        $source = $this->normalizeUri($source);
        $destination   = $this->normalizeUri($destination);

        $sourceScheme = $this->registry->resolve($source->scheme());
        $destScheme   = $this->registry->resolve($destination->scheme());

        $sourcePath = Path::absolutePath($sourceScheme, $source->path());
        $destPath   = Path::absolutePath($destScheme, $destination->path());

        // same scheme → rename
        if ($source->scheme() === $destination->scheme()) {
            $sourceScheme->store()->rename($sourcePath, $destPath);
            return true;
        }

        // cross-scheme → copy + delete
        $this->copy($source, $destination);
        $this->delete($source);

        return true;
    }


    public function exists(string|Uri $uri): bool
    {
        $uri = $this->normalizeUri($uri);
        $scheme = $this->registry->resolve($uri->scheme());

        $absolutePath = Path::absolutePath($scheme, $uri->path());

        return $scheme->store()->exists($absolutePath);
    }


    public function stats(string|Uri $uri): array
    {
        $uri = $this->normalizeUri($uri);
        $scheme = $this->registry->resolve($uri->scheme());

        $absolutePath = Path::absolutePath($scheme, $uri->path());

        return $scheme->store()->stats($absolutePath);
    }
}
