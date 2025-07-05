<?php

namespace Arbor\http\components;

/**
 * Headers class for managing HTTP message headers.
 * 
 * This class provides methods to manipulate HTTP headers in a case-insensitive manner
 * while preserving the original case format for output. It supports both single and
 * multiple header values per name.
 * 
 * @package Arbor\http\components
 */
class Headers
{
    /**
     * Associative array of headers where keys are header names and values are arrays of header values.
     * 
     * @var array<string, string[]>
     */
    protected array $headers = [];

    /**
     * Headers constructor.
     *
     * @param array<string, string|string[]> $headers Initial headers array with header names as keys
     */
    public function __construct(array $headers = [])
    {
        foreach ($headers as $name => $value) {
            $this->set($name, $value);
        }
    }

    /**
     * Get all headers with their values.
     *
     * @return array<string, string[]> All headers with original case preserved
     */
    public function getAll(): array
    {
        return $this->headers;
    }

    /**
     * Get header values by case-insensitive name.
     *
     * @param string $name Header name
     * @param string[] $default Default value if header doesn't exist
     * @return string[] Array of header values
     */
    public function get(string $name, array $default = []): array
    {
        $name = $this->normalizeHeaderName($name);

        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $name) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Get the first value of a header or return a default value if not found.
     *
     * @param string $name Header name
     * @param string $default Default value if header doesn't exist or is empty
     * @return string First header value or default value
     */
    public function getFirst(string $name, string $default = ''): string
    {
        return $this->get($name)[0] ?? $default;
    }

    /**
     * Check if a header exists by case-insensitive name.
     *
     * @param string $name Header name
     * @return bool True if header exists, false otherwise
     */
    public function has(string $name): bool
    {
        $name = $this->normalizeHeaderName($name);

        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set a header, replacing any existing values with the same name.
     *
     * @param string $name Header name
     * @param string|string[] $value Header value(s)
     * @return self For method chaining
     * @throws \InvalidArgumentException If header values are not strings or numbers
     */
    public function set(string $name, $value): self
    {
        $normalized = $this->normalizeHeaderName($name);
        $originalName = $this->getHeaderOriginalCase($normalized) ?? $this->formatHeaderName($name);

        // Remove any existing header with this name
        $this->remove($name);

        // Set the new value
        $this->headers[$originalName] = $this->normalizeHeaderValue($value);

        return $this;
    }

    /**
     * Add a header value to an existing header or create it if it doesn't exist.
     *
     * @param string $name Header name
     * @param string|string[] $value Header value(s) to add
     * @return self For method chaining
     * @throws \InvalidArgumentException If header values are not strings or numbers
     */
    public function add(string $name, $value): self
    {
        $normalized = $this->normalizeHeaderName($name);
        $originalName = $this->getHeaderOriginalCase($normalized) ?? $this->formatHeaderName($name);
        $value = $this->normalizeHeaderValue($value);

        if ($this->has($name)) {
            $this->headers[$originalName] = array_merge($this->get($name), $value);
        } else {
            $this->headers[$originalName] = $value;
        }

        return $this;
    }

    /**
     * Remove a header by case-insensitive name.
     *
     * @param string $name Header name to remove
     * @return self For method chaining
     */
    public function remove(string $name): self
    {
        $normalized = $this->normalizeHeaderName($name);

        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $normalized) {
                unset($this->headers[$key]);
                break;
            }
        }

        return $this;
    }

    /**
     * Create a new Headers instance with added header (immutable operation).
     *
     * @param string $name Header name
     * @param string|string[] $value Header value(s)
     * @return Headers New Headers instance with the added header
     * @throws \InvalidArgumentException If header values are not strings or numbers
     */
    public function withHeader(string $name, $value): Headers
    {
        $new = clone $this;
        $new->set($name, $value);
        return $new;
    }

    /**
     * Create a new Headers instance with appended header value (immutable operation).
     *
     * @param string $name Header name
     * @param string|string[] $value Header value(s) to append
     * @return Headers New Headers instance with the appended header value
     * @throws \InvalidArgumentException If header values are not strings or numbers
     */
    public function withAddedHeader(string $name, $value): Headers
    {
        $new = clone $this;
        $new->add($name, $value);
        return $new;
    }

    /**
     * Create a new Headers instance without the specified header (immutable operation).
     *
     * @param string $name Header name to remove
     * @return Headers New Headers instance without the specified header
     */
    public function withoutHeader(string $name): Headers
    {
        $new = clone $this;
        $new->remove($name);
        return $new;
    }

    /**
     * Normalize header name to lowercase for case-insensitive comparison.
     *
     * @param string $name Header name
     * @return string Normalized lowercase name
     */
    protected function normalizeHeaderName(string $name): string
    {
        return strtolower(trim($name));
    }

    /**
     * Format header name to standard HTTP case format (e.g., Content-Type, X-Forwarded-For).
     * Capitalizes first letter and letters following hyphens.
     *
     * @param string $name Header name
     * @return string Properly formatted header name
     */
    protected function formatHeaderName(string $name): string
    {
        $name = trim($name);
        $name = ucwords(strtolower($name), '-');
        return $name;
    }

    /**
     * Get the original case of a header if it exists.
     *
     * @param string $normalized Normalized (lowercase) header name
     * @return string|null Original header name with proper case or null if not found
     */
    protected function getHeaderOriginalCase(string $normalized): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $normalized) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Normalize header value to an array of strings.
     *
     * @param string|string[]|int|float|int[]|float[] $value Header value(s)
     * @return string[] Normalized array of string values
     * @throws \InvalidArgumentException If values are not strings or numbers
     */
    protected function normalizeHeaderValue($value): array
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        return array_map(function ($v) {
            if (!is_string($v) && !is_numeric($v)) {
                throw new \InvalidArgumentException('Header values must be strings or numbers');
            }
            return (string) $v;
        }, $value);
    }

    /**
     * Convert headers to a simple associative array format.
     * 
     * This converts from the internal format (where values are always arrays)
     * to a simple format where single values are strings and multiple values remain arrays.
     *
     * @return array<string, string|string[]> Headers as key-value pairs
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->headers as $name => $values) {
            // Convert single-value arrays to strings for cleaner output
            $result[$name] = count($values) === 1 ? $values[0] : $values;
        }

        return $result;
    }
}
