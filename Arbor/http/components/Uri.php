<?php

namespace Arbor\http\components;

use Exception;

/**
 * Class Uri
 * 
 * URI handling component that implements immutable URI manipulation
 * based on RFC 3986 specifications.

 * Represents and manipulates Uniform Resource Identifiers (URIs) according to RFC 3986.
 * This class provides an immutable object for URI manipulation where each mutation
 * returns a new instance, leaving the original unmodified.
 * 
 *
 * @package Arbor\http\components
 * 
 */
class Uri
{
    /**
     * URI scheme component (e.g., 'http', 'https')
     */
    private string $scheme = '';

    /**
     * URI user information component (username[:password])
     */
    private string $userInfo = '';

    /**
     * URI host component
     */
    private string $host = '';

    /**
     * URI port component
     */
    private ?int $port = null;

    /**
     * URI path component
     */
    private string $path = '';

    /**
     * URI query component (after '?')
     */
    private string $query = '';

    /**
     * URI fragment component (after '#')
     */
    private string $fragment = '';

    /**
     * Constructor for creating a new URI instance
     *
     * @param string $uri A URI string to parse and initialize from
     * @throws Exception If the URI cannot be parsed
     */
    public function __construct(string $uri = '')
    {
        if ($uri !== '') {
            $parts = parse_url($uri);
            if ($parts === false) {
                throw new Exception("Unable to parse URI: {$uri}");
            }

            $this->scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
            $this->host = isset($parts['host']) ? strtolower($parts['host']) : '';
            $this->port = isset($parts['port']) ? $this->filterPort((int)$parts['port']) : null;
            $this->userInfo = $parts['user'] ?? '';

            if (isset($parts['pass'])) {
                $this->userInfo .= ':' . $parts['pass'];
            }

            $this->path = $this->filterPath($parts['path'] ?? '');
            $this->query = $this->filterQueryAndFragment($parts['query'] ?? '');
            $this->fragment = $this->filterQueryAndFragment($parts['fragment'] ?? '');
        }
    }

    /**
     * Returns the scheme component of the URI
     *
     * @return string The URI scheme
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * Returns the authority component of the URI
     * The authority consists of the host, an optional userinfo, and an optional port
     *
     * @return string The URI authority
     */
    public function getAuthority(): string
    {
        if ($this->host === '') {
            return '';
        }

        $authority = $this->host;

        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->port !== null && $this->port !== $this->getDefaultPort($this->scheme)) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * Returns the user information component of the URI
     *
     * @return string The URI userinfo
     */
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    /**
     * Returns the host component of the URI
     *
     * @return string The URI host
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Returns the port component of the URI
     *
     * @return int|null The URI port or null if not specified
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * Returns the path component of the URI
     *
     * @return string The URI path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Returns the query string component of the URI
     *
     * @return string The URI query
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Returns the fragment component of the URI
     *
     * @return string The URI fragment
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * Returns a new instance with the specified scheme
     *
     * @param string $scheme The scheme to use
     * @return self A new instance with the specified scheme
     */
    public function withScheme(string $scheme): self
    {
        $scheme = strtolower($scheme);
        if ($this->scheme === $scheme) {
            return $this;
        }

        $new = clone $this;
        $new->scheme = $scheme;
        return $new;
    }

    /**
     * Returns a new instance with the specified user information
     *
     * @param string $user The user name to use
     * @param string|null $password The password to use
     * @return self A new instance with the specified user information
     */
    public function withUserInfo(string $user, ?string $password = null): self
    {
        $info = $user;
        if ($password !== null && $password !== '') {
            $info .= ':' . $password;
        }

        if ($this->userInfo === $info) {
            return $this;
        }

        $new = clone $this;
        $new->userInfo = $info;
        return $new;
    }

    /**
     * Returns a new instance with the specified host
     *
     * @param string $host The host to use
     * @return self A new instance with the specified host
     */
    public function withHost(string $host): self
    {
        $host = strtolower($host);
        if ($this->host === $host) {
            return $this;
        }

        $new = clone $this;
        $new->host = $host;
        return $new;
    }

    /**
     * Returns a new instance with the specified port
     *
     * @param int|null $port The port to use
     * @return self A new instance with the specified port
     * @throws Exception If the port is invalid
     */
    public function withPort(?int $port): self
    {
        $port = $this->filterPort($port);

        if ($this->port === $port) {
            return $this;
        }

        $new = clone $this;
        $new->port = $port;
        return $new;
    }

    /**
     * Returns a new instance with the specified path
     *
     * @param string $path The path to use
     * @return self A new instance with the specified path
     */
    public function withPath(string $path): self
    {
        $path = $this->filterPath($path);

        if ($this->path === $path) {
            return $this;
        }

        $new = clone $this;
        $new->path = $path;
        return $new;
    }

    /**
     * Returns a new instance with the specified query string
     *
     * @param string $query The query string to use
     * @return self A new instance with the specified query string
     */
    public function withQuery(string $query): self
    {
        $query = $this->filterQueryAndFragment($query);

        if ($this->query === $query) {
            return $this;
        }

        $new = clone $this;
        $new->query = $query;
        return $new;
    }

    /**
     * Returns a new instance with the specified fragment
     *
     * @param string $fragment The fragment to use
     * @return self A new instance with the specified fragment
     */
    public function withFragment(string $fragment): self
    {
        $fragment = $this->filterQueryAndFragment($fragment);

        if ($this->fragment === $fragment) {
            return $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;
        return $new;
    }

    /**
     * Returns a string representation of the URI
     *
     * @return string The string representation of the URI
     */
    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        $authority = $this->getAuthority();
        if ($authority !== '') {
            $uri .= '//' . $authority;
        }

        if ($this->path !== '') {
            if ($authority !== '' && !str_starts_with($this->path, '/')) {
                $uri .= '/' . $this->path;
            } elseif ($authority === '' && str_starts_with($this->path, '//')) {
                $uri .= '/' . ltrim($this->path, '/');
            } else {
                $uri .= $this->path;
            }
        }

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    // --- Private Utility Methods ---

    /**
     * Validates and filters the port number
     *
     * @param int|null $port The port to filter
     * @return int|null The filtered port
     * @throws Exception If the port is not valid
     */
    private function filterPort(?int $port): ?int
    {
        if ($port === null) {
            return null;
        }

        if ($port < 1 || $port > 65535) {
            throw new Exception("Invalid port: {$port}");
        }

        return $port;
    }

    /**
     * Filters and encodes the path component
     *
     * @param string $path The path to filter
     * @return string The filtered path
     */
    private function filterPath(string $path): string
    {
        return preg_replace_callback('/[^a-zA-Z0-9\-._~!$&\'()*+,;=:@\/]/u', function (array $matches): string {
            return rawurlencode($matches[0]);
        }, $path) ?? '';
    }

    /**
     * Filters and encodes the query or fragment component
     *
     * @param string $input The query or fragment to filter
     * @return string The filtered query or fragment
     */
    private function filterQueryAndFragment(string $input): string
    {
        return preg_replace_callback('/[^a-zA-Z0-9\-._~!$&\'()*+,;=:@\/?]/u', function (array $matches): string {
            return rawurlencode($matches[0]);
        }, $input) ?? '';
    }

    /**
     * Returns the default port for a given scheme
     *
     * @param string $scheme The scheme to get the default port for
     * @return int|null The default port for the scheme or null if no default
     */
    private function getDefaultPort(string $scheme): ?int
    {
        return match ($scheme) {
            'http' => 80,
            'https' => 443,
            default => null
        };
    }
}
