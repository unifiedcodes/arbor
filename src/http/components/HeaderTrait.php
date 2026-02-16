<?php

namespace Arbor\http\components;

use Arbor\http\components\Headers;
use LogicException;

/**
 * Trait HeaderTrait
 * 
 * Provides standardized HTTP header manipulation functionality.
 * Implements methods for reading, adding, replacing, and removing headers.
 * 
 * @package Arbor\http\traits
 */
trait HeaderTrait
{
    /** @var Headers The collection of headers */
    protected Headers $headers;

    /**
     * Ensures the headers property has been initialized.
     * 
     * @throws LogicException If headers have not been initialized
     * @return void
     */
    protected function ensureHeadersInitialized(): void
    {
        if (!isset($this->headers)) {
            throw new LogicException('Headers have not been initialized.');
        }
    }

    /**
     * Returns all headers.
     * 
     * @return array<string, string[]> An associative array of the message's headers
     */
    public function getHeaders(): array
    {
        $this->ensureHeadersInitialized();
        return $this->headers->getAll();
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     * 
     * @param string $name Case-insensitive header name
     * @return bool True if header exists, false otherwise
     */
    public function hasHeader(string $name): bool
    {
        $this->ensureHeadersInitialized();
        return $this->headers->has($name);
    }

    /**
     * Returns all values for a given header.
     * 
     * @param string $name Case-insensitive header name
     * @return string[] An array of string values for the header
     */
    public function getHeader(string $name): array
    {
        $this->ensureHeadersInitialized();
        return $this->headers->get($name, []);
    }

    /**
     * Returns a comma-separated string of the values for a given header.
     * 
     * @param string $name Case-insensitive header name
     * @return string A string of comma-separated values
     */
    public function getHeaderLine(string $name): string
    {
        $header = $this->getHeader($name);
        return implode(', ', $header);
    }

    /**
     * Returns an instance with the specified header set, replacing any existing values.
     * 
     * @param string $name Case-insensitive header name
     * @param string|string[] $value Header value(s)
     * @return static A new instance with the modified header
     */
    public function withHeader(string $name, string|array $value): static
    {
        $this->ensureHeadersInitialized();
        $new = clone $this;
        $new->headers = $this->headers->withHeader($name, $value);
        return $new;
    }

    /**
     * Returns an instance with the specified value appended to the header.
     * 
     * @param string $name Case-insensitive header name
     * @param string|string[] $value Header value(s) to append
     * @return static A new instance with the appended header values
     */
    public function withAddedHeader(string $name, string|array $value): static
    {
        $this->ensureHeadersInitialized();
        $new = clone $this;
        $new->headers = $this->headers->withAddedHeader($name, $value);
        return $new;
    }

    /**
     * Returns an instance without the specified header.
     * 
     * @param string $name Case-insensitive header name to remove
     * @return static A new instance without the specified header
     */
    public function withoutHeader(string $name): static
    {
        $this->ensureHeadersInitialized();
        $new = clone $this;
        $new->headers = $this->headers->withoutHeader($name);
        return $new;
    }
}
