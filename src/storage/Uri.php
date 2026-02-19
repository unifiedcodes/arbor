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

        $scheme = self::assertValidScheme($scheme);

        $path = Path::normalizeRelativePath($path);

        return new self(
            strtolower($scheme),
            $path
        );
    }


    public static function fromParts(string $scheme, string $path, ?string $fileName = null): self
    {
        $scheme = self::assertValidScheme($scheme);

        // normalizing path formatting.
        $path = self::sanatizePath($path);

        // using filename if provided.
        if ($fileName !== null) {
            $fileName = self::assertValidFileName($fileName);
            $path = rtrim($path, '/') . '/' . $fileName;
        }

        return new self(
            $scheme,
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
        $path = self::sanatizePath($path);

        return new self(
            $this->scheme,
            Path::normalizeRelativePath($path)
        );
    }


    public function withFileName(string $fileName): self
    {
        $fileName = self::assertValidFileName($fileName);

        $basePath = rtrim($this->path, '/') . '/';

        return new self(
            $this->scheme,
            Path::normalizeRelativePath($basePath . $fileName)
        );
    }


    private static function assertValidFileName(string $fileName): string
    {
        $fileName = trim($fileName);

        if ($fileName === '') {
            throw new InvalidArgumentException('Filename cannot be empty');
        }

        // Disallow path injection
        if (str_contains($fileName, '/') || str_contains($fileName, '\\')) {
            throw new InvalidArgumentException('Filename must not contain directory separators');
        }

        return $fileName;
    }


    private static function assertValidScheme(string $scheme): string
    {
        $scheme = trim($scheme);

        if ($scheme === '') {
            throw new InvalidArgumentException('Scheme cannot be empty');
        }

        if (str_contains($scheme, '://')) {
            throw new InvalidArgumentException('Scheme must not contain "://"');
        }

        // RFC 3986 compliant scheme validation
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*$/', $scheme)) {
            throw new InvalidArgumentException(
                "Invalid scheme format: {$scheme}"
            );
        }

        return strtolower($scheme);
    }


    public function __toString(): string
    {
        return $this->scheme . '://' . $this->path;
    }


    public function toString(): string
    {
        return (string) $this;
    }


    private static function sanatizePath(string $path): string
    {
        // Trim surrounding whitespace
        $path = trim($path);

        // Normalize Windows separators early
        $path = str_replace('\\', '/', $path);

        // Remove leading slashes to enforce relative semantics
        $path = ltrim($path, '/');

        return $path;
    }
}
