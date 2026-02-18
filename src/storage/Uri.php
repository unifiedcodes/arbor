<?php

namespace Arbor\storage;


use InvalidArgumentException;


final class Uri
{
    private string $scheme;
    private string $namespace;
    private string $path;


    private function __construct(string $scheme, string $path)
    {
        $this->scheme = $scheme;
        $this->path   = $path;
    }


    public static function fromString(string $uri): self
    {
        if (!str_contains($uri, '://')) {
            throw new InvalidArgumentException("Invalid URI: {$uri}");
        }

        [$scheme, $path] = explode('://', $uri, 2);

        if ($scheme === '') {
            throw new InvalidArgumentException("URI scheme missing");
        }

        $path = Path::normalizeRelativePath($path);

        return new self(
            strtolower($scheme),
            $path
        );
    }


    public static function fromParts(string $scheme, string $path): self
    {
        return new self(
            strtolower($scheme),
            Path::normalizeRelativePath($path)
        );
    }


    public function scheme(): string
    {
        return $this->scheme;
    }


    public function path(): string
    {
        return $this->path;
    }

    public function withPath(string $path): self
    {
        return new self(
            $this->scheme,
            Path::normalizeRelativePath($path)
        );
    }


    public function withFileName(string $fileName): self
    {
        $fileName = trim($fileName);

        if ($fileName === '') {
            throw new InvalidArgumentException('Filename cannot be empty');
        }

        // Disallow path injection
        if (str_contains($fileName, '/') || str_contains($fileName, '\\')) {
            throw new InvalidArgumentException('Filename must not contain directory separators');
        }

        $basePath = rtrim($this->path, '/') . '/';

        return new self(
            $this->scheme,
            Path::normalizeRelativePath($basePath . $fileName)
        );
    }



    public function __toString(): string
    {
        return $this->scheme . '://' . $this->path;
    }


    public function toString(): string
    {
        return (string) $this;
    }
}
