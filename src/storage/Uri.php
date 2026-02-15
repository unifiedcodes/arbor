<?php

namespace Arbor\storage;


use InvalidArgumentException;


final class Uri
{
    private string $scheme;
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


    public function __toString(): string
    {
        return $this->scheme . '://' . $this->path;
    }


    public function toString(): string
    {
        return (string) $this;
    }
}
