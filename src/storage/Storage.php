<?php

namespace Arbor\storage;


use Arbor\storage\stores\StoreInterface;
use Arbor\storage\Registry;
use Arbor\storage\Path;
use Arbor\storage\streams\StreamInterface;
use Arbor\storage\streams\StreamFactory;


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

    protected function resolveStore(string $uri): StoreInterface
    {
        $parsed = Path::parseUri($uri);

        $scheme = $this->registry->resolve($parsed['scheme']);

        return $scheme->store();
    }

    // path methods
    public function url(string $uri): string
    {
        $parsed = Path::parseUri($uri);

        return Path::publicUrl($parsed['scheme'], $parsed['path']);
    }


    // IO methods
    public function read(string $uri): StreamInterface
    {
        $parsed = Path::parseUri($uri);
        $scheme = $this->registry->resolve($parsed['scheme']);

        $absolutePath = Path::absolutePath($scheme, $parsed['path']);

        $stream = $scheme->store()->read($absolutePath);

        return $stream;
    }


    public function write(string $uri, string $contents): bool
    {
        $parsed = Path::parseUri($uri);
        $scheme = $this->registry->resolve($parsed['scheme']);

        $absolutePath = Path::absolutePath($scheme, $parsed['path']);

        $stream = StreamFactory::fromString($contents);

        $scheme->store()->write($absolutePath, $stream);

        return true;
    }


    public function append(string $uri, string $contents): bool
    {
        $parsed = Path::parseUri($uri);
        $scheme = $this->registry->resolve($parsed['scheme']);
        $absolutePath = Path::absolutePath($scheme, $parsed['path']);

        if (!$scheme->store()->exists($absolutePath)) {
            return $this->write($uri, $contents);
        }

        $existing = $scheme->store()->read($absolutePath);
        $out = StreamFactory::empty();

        while (!$existing->eof()) {
            $out->write($existing->read(8192));
        }

        $out->write($contents);

        $scheme->store()->write($absolutePath, $out);

        return true;
    }


    public function delete(string $uri): bool
    {
        $parsed = Path::parseUri($uri);
        $scheme = $this->registry->resolve($parsed['scheme']);

        $absolutePath = Path::absolutePath($scheme, $parsed['path']);

        $scheme->store()->delete($absolutePath);

        return true;
    }


    public function copy(string $source, string $destination): bool
    {
        $sourceParsed = Path::parseUri($source);
        $destParsed   = Path::parseUri($destination);

        $sourceScheme = $this->registry->resolve($sourceParsed['scheme']);
        $destScheme   = $this->registry->resolve($destParsed['scheme']);

        $sourcePath = Path::absolutePath($sourceScheme, $sourceParsed['path']);
        $destPath   = Path::absolutePath($destScheme, $destParsed['path']);


        $stream = $sourceScheme->store()->read($sourcePath);
        $destScheme->store()->write($destPath, $stream);

        return true;
    }


    public function move(string $source, string $destination): bool
    {
        $sourceParsed = Path::parseUri($source);
        $destParsed   = Path::parseUri($destination);

        $sourceScheme = $this->registry->resolve($sourceParsed['scheme']);
        $destScheme   = $this->registry->resolve($destParsed['scheme']);

        $sourcePath = Path::absolutePath($sourceScheme, $sourceParsed['path']);
        $destPath   = Path::absolutePath($destScheme, $destParsed['path']);

        // same scheme → rename
        if ($sourceParsed['scheme'] === $destParsed['scheme']) {
            $sourceScheme->store()->rename($sourcePath, $destPath);
            return true;
        }

        // cross-scheme → copy + delete
        $this->copy($source, $destination);
        $this->delete($source);

        return true;
    }


    public function exists(string $uri): bool
    {
        $parsed = Path::parseUri($uri);
        $scheme = $this->registry->resolve($parsed['scheme']);

        $absolutePath = Path::absolutePath($scheme, $parsed['path']);

        return $scheme->store()->exists($absolutePath);
    }


    public function stats(string $uri): array
    {
        $parsed = Path::parseUri($uri);
        $scheme = $this->registry->resolve($parsed['scheme']);

        $absolutePath = Path::absolutePath($scheme, $parsed['path']);

        return $scheme->store()->stats($absolutePath);
    }
}
