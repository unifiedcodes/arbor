<?php

namespace Arbor\view;


use InvalidArgumentException;


/**
 * Represents a registered URI scheme with its root directory and optional base URL.
 * Immutable value object for managing scheme configuration.
 */
final class Scheme
{
    /**
     * Constructor for the Scheme.
     *
     * @param string $name The scheme name.
     * @param string $root The root directory path for the scheme.
     * @param string|null $baseUrl The optional base URL for asset resolution.
     * @throws InvalidArgumentException If the name is empty or root is invalid.
     */
    public function __construct(
        private string $name,
        private string $root,
        private ?string $baseUrl = null
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('Scheme name cannot be empty.');
        }

        $root = trim($root);

        if ($root === '' || $root === '/') {
            throw new InvalidArgumentException('Scheme root cannot be empty or "/".');
        }

        $this->root = normalizeDirPath($root);
    }

    /**
     * Gets the scheme name.
     *
     * @return string The scheme name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Gets the root directory path.
     *
     * @return string The root directory path.
     */
    public function root(): string
    {
        return $this->root;
    }

    /**
     * Gets the base URL for asset resolution.
     *
     * @return string|null The base URL or null if not set.
     */
    public function baseUrl(): ?string
    {
        return $this->baseUrl;
    }

    /**
     * Checks if this scheme is public (has a base URL).
     * Public schemes can be used for asset resolution.
     *
     * @return bool True if the scheme has a base URL, false otherwise.
     */
    public function isPublic(): bool
    {
        return !empty($this->baseUrl);
    }
}
