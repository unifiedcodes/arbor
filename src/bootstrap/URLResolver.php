<?php

namespace Arbor\bootstrap;

/**
 * URLResolver - Utility class for handling URL resolution and path detection
 * 
 * This class provides static methods to:
 * - Detect the root URI of the application
 * - Get the full requested URI from the client
 * - Extract application keys from URI paths
 * 
 * All methods are designed to work with $_SERVER superglobal variables
 * and handle various edge cases in URL parsing.
 * 
 * @package Arbor\bootstrap
 */
class URLResolver
{
    /**
     * Detects the root URI of the application based on the front controller
     * 
     * This method constructs the base URI by analyzing the server environment
     * and removing the front controller from the script path. It handles
     * both HTTP and HTTPS protocols and ensures proper trailing slash handling.
     * 
     * @param string $frontController The front controller filename (e.g., 'index.php')
     * @return string The root URI with trailing slash (e.g., 'https://example.com/app/')
     */
    public static function detectRootURI(string $frontController): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $httpHost   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $https      = ($_SERVER['HTTPS'] ?? 'off') === 'on';
        $protocol   = $https ? 'https' : 'http';

        // Normalize slashes and remove front controller path
        $basePath = rtrim(str_replace($frontController, '', $scriptName), '/');

        // Edge case: basePath ends up empty → site is at root
        if ($basePath === '') {
            $basePath = '/';
        }

        $uri = "{$protocol}://{$httpHost}{$basePath}/";

        return rtrim($uri, '/') . '/';
    }

    /**
     * Gets the complete requested URI from the client
     * 
     * Constructs the full URL that was requested by the client, including
     * protocol, host, path, and query string. Falls back to sensible defaults
     * when server variables are not available.
     * 
     * @return string The complete requested URI (e.g., 'https://example.com/path?query=value')
     */
    public static function getRequestedURI(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host   = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
        $uri    = $_SERVER['REQUEST_URI'] ?? '/'; // includes path and query string

        return "$scheme://$host$uri";
    }

    /**
     * Extracts the application key (first path segment) from the requested URI
     * 
     * Given a root URI, this method determines the first path segment after
     * the root, which typically represents the application or module identifier.
     * Uses manual string scanning for optimal performance.
     * 
     * Examples:
     * - Root: 'https://example.com/', Request: 'https://example.com/admin/users' → 'admin'
     * - Root: 'https://example.com/app/', Request: 'https://example.com/app/api/v1' → 'api'
     * 
     * @param string $rootUri The root URI of the application
     * @return string The first path segment after root, or empty string if none found
     */
    public static function getAppKey(string $rootUri): string
    {
        $requestUri = static::getRequestedURI();
        $relativePath = '';

        if ($rootUri && str_starts_with($requestUri, $rootUri)) {
            $relative = substr($requestUri, strlen($rootUri));
            $relativePath = '/' . ltrim($relative, '/');
        }

        // Manually scan characters to find first segment
        // trade off to save performance cost of more readable ways.
        $length = strlen($relativePath);
        $start = 0;

        while ($start < $length && $relativePath[$start] === '/') {
            $start++;
        }

        if ($start === $length) return '';

        $end = strpos($relativePath, '/', $start);
        return $end === false ? substr($relativePath, $start) : substr($relativePath, $start, $end - $start);
    }
}
