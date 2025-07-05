<?php

namespace Arbor\http\components;

/**
 * Cookies class for managing HTTP cookies.
 * 
 * This class provides a comprehensive way to manage HTTP cookies, including
 * getting, setting, checking, removing, parsing and formatting cookies for
 * HTTP requests and responses.
 * 
 * @package Arbor\http\components
 */
class Cookies
{
    /**
     * Storage for cookie values.
     * 
     * @var array<string, mixed>
     */
    protected array $cookies = [];

    /**
     * Cookies constructor.
     *
     * @param array<string, mixed> $cookies Initial cookies to populate the instance
     */
    public function __construct(array $cookies = [])
    {
        $this->cookies = $cookies;
    }

    /**
     * Get all cookies.
     *
     * @return array<string, mixed> All cookies stored in this instance
     */
    public function getAll(): array
    {
        return $this->cookies;
    }

    /**
     * Get a cookie value by name.
     *
     * @param string $name Cookie name to retrieve
     * @param mixed $default Default value to return if cookie doesn't exist
     * @return mixed Cookie value if it exists, otherwise the default value
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return $this->cookies[$name] ?? $default;
    }

    /**
     * Check if a cookie exists.
     *
     * @param string $name Cookie name to check
     * @return bool True if the cookie exists, false otherwise
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->cookies);
    }

    /**
     * Set a cookie value.
     *
     * @param string $name Cookie name to set
     * @param mixed $value Cookie value to store
     * @return self For method chaining
     */
    public function set(string $name, mixed $value): self
    {
        $this->cookies[$name] = $value;
        return $this;
    }

    /**
     * Remove a cookie.
     *
     * @param string $name Cookie name to remove
     * @return self For method chaining
     */
    public function remove(string $name): self
    {
        unset($this->cookies[$name]);
        return $this;
    }

    /**
     * Create a cookie to be sent in HTTP response.
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param array<string, mixed> $options Cookie options including:
     *        - expires: int|string - Expiration timestamp or formatted date string
     *        - path: string - Cookie path
     *        - domain: string - Cookie domain
     *        - secure: bool - Whether the cookie should only be sent over HTTPS
     *        - httponly: bool - Whether the cookie is accessible only through HTTP
     *        - samesite: string - SameSite attribute value (None, Lax, Strict)
     * @return array<string, mixed> Cookie data for response
     */
    public function createCookie(string $name, string $value, array $options = []): array
    {
        $defaults = [
            'expires' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => false,
            'samesite' => ''
        ];

        $options = array_merge($defaults, $options);

        return [
            'name' => $name,
            'value' => $value,
            'options' => $options
        ];
    }

    /**
     * Parse a cookie string from a Cookie header.
     *
     * Extracts individual cookies from the HTTP Cookie header
     * and returns them as an associative array.
     *
     * @param string $cookieHeader Cookie header string from HTTP request
     * @return array<string, string> Parsed cookies as name-value pairs
     */
    public static function parseCookieHeader(string $cookieHeader): array
    {
        $cookies = [];
        $parts = explode(';', $cookieHeader);

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }

            $cookieParts = explode('=', $part, 2);
            $name = trim($cookieParts[0]);
            $value = isset($cookieParts[1]) ? trim($cookieParts[1]) : '';

            // Handle quoted values
            if (strlen($value) > 1 && substr($value, 0, 1) === '"' && substr($value, -1) === '"') {
                $value = substr($value, 1, -1);
            }

            $cookies[$name] = urldecode($value);
        }

        return $cookies;
    }

    /**
     * Format cookies to a string for sending in a Cookie header.
     *
     * @return string Formatted cookie string suitable for an HTTP Cookie header
     */
    public function toHeaderString(): string
    {
        $parts = [];

        foreach ($this->cookies as $name => $value) {
            $parts[] = $name . '=' . urlencode((string)$value);
        }

        return implode('; ', $parts);
    }

    /**
     * Create a Set-Cookie header value for a given cookie.
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param array<string, mixed> $options Cookie options including:
     *        - expires: int|string - Expiration timestamp or formatted date string
     *        - path: string - Cookie path
     *        - domain: string - Cookie domain
     *        - secure: bool - Whether the cookie should only be sent over HTTPS
     *        - httponly: bool - Whether the cookie is accessible only through HTTP
     *        - samesite: string - SameSite attribute value (None, Lax, Strict)
     * @return string Set-Cookie header value
     */
    public static function createSetCookieHeader(string $name, string $value, array $options = []): string
    {
        $defaults = [
            'expires' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => false,
            'samesite' => ''
        ];

        $options = array_merge($defaults, $options);
        $header = urlencode($name) . '=' . urlencode($value);

        if ($options['expires'] > 0) {
            // Format the expires time into a valid HTTP date
            if (is_numeric($options['expires'])) {
                $expiresTime = gmdate('D, d M Y H:i:s T', (int)$options['expires']);
            } else {
                $expiresTime = (string)$options['expires'];
            }
            $header .= '; Expires=' . $expiresTime;
        }

        if (!empty($options['path'])) {
            $header .= '; Path=' . $options['path'];
        }

        if (!empty($options['domain'])) {
            $header .= '; Domain=' . $options['domain'];
        }

        if ($options['secure']) {
            $header .= '; Secure';
        }

        if ($options['httponly']) {
            $header .= '; HttpOnly';
        }

        if (!empty($options['samesite'])) {
            $header .= '; SameSite=' . $options['samesite'];
        }

        return $header;
    }

    /**
     * Create a new Cookies instance with added cookie.
     *
     * This is an immutable operation that returns a new instance.
     *
     * @param string $name Cookie name
     * @param mixed $value Cookie value
     * @return Cookies New Cookies instance with the added cookie
     */
    public function withCookie(string $name, mixed $value): Cookies
    {
        $new = clone $this;
        $new->set($name, $value);
        return $new;
    }

    /**
     * Create a new Cookies instance without the specified cookie.
     *
     * This is an immutable operation that returns a new instance.
     *
     * @param string $name Cookie name to remove
     * @return Cookies New Cookies instance without the specified cookie
     */
    public function withoutCookie(string $name): Cookies
    {
        $new = clone $this;
        $new->remove($name);
        return $new;
    }
}
