<?php

namespace Arbor\router;

use Exception;

/**
 * URLBuilder handles dynamic URL construction with parameter replacement
 * 
 * @package Arbor\Router
 */
final class URLBuilder
{
    /**
     * @var array<string, string> Named route registry
     */
    private array $namedRegistry = [];

    /**
     * Register a named route path
     * 
     * @param string $name Unique route identifier
     * @param string $path URL pattern with {placeholders}
     * @return void
     */
    public function add(string $name, string $path): void
    {
        $this->namedRegistry[$name] = $path;
    }

    /**
     * Build URL for named route with parameters
     * 
     * @param string $routeName Registered route identifier
     * @param array<string|int, mixed> $parameters Replacement values
     * @return string Constructed URL
     * @throws Exception If route not found or missing parameters
     */
    public function getRelativeURL(string $routeName, array $parameters = []): string
    {
        $route = $this->namedRegistry[$routeName] ?? null;

        if (!$route) {
            throw new Exception("Route name not defined: {$routeName}");
        }

        return $this->buildRouteUrl($route, $parameters);
    }


    /**
     * Generates an absolute URL by combining a base URL with a named route's path.
     * 
     * This method constructs a complete URL by prepending the provided base URL to the 
     * relative path generated from the named route. It handles edge cases like empty
     * paths and ensures proper URL concatenation without duplicate slashes.
     *
     * @param string $baseURL The base URL of the application (e.g., "https://example.com").
     *                       Trailing slashes are automatically handled.
     * @param string $routeName The name of the registered route to generate the path for.
     * @param array<string|int, mixed> $parameters Optional parameters to replace route placeholders.
     * 
     * @return string The absolute URL in the format: "{baseURL}/{relativePath}".
     *                Returns "/" if both baseURL and relativePath are empty.
     * 
     * @throws Exception If the route is not registered or required parameters are missing.
     * 
     * @example
     *   $builder->add('product', '/products/{id}');
     *   echo $builder->getAbsoluteURL('https://example.com', 'product', ['id' => 42]);
     *   // Output: "https://example.com/products/42"
     */
    public function getAbsoluteURL(string $baseURL, string $routeName, array $parameters = []): string
    {
        $relativeUrl = $this->getRelativeURL($routeName, $parameters);
        $baseURL = rtrim($baseURL, '/');
        $relativeUrl = ltrim($relativeUrl, '/');

        if ($relativeUrl === '') {
            return $baseURL ?: '/';
        }

        return $baseURL . '/' . $relativeUrl;
    }

    /**
     * Replace placeholders in URL pattern with parameters
     * 
     * @param string $url URL pattern with {placeholders}
     * @param array<string|int, mixed> $parameters Replacement values
     * @return string Constructed URL
     * @throws Exception For missing required parameters
     */
    private function buildRouteUrl(string $url, array $parameters): string
    {
        $index = 0;

        return (string) preg_replace_callback(
            '/\{([^}]+)\}/',
            function (array $matches) use ($parameters, &$index): string {
                $placeholder = $matches[1];
                $isOptional = str_ends_with($placeholder, '?');
                $paramName = $isOptional ? substr($placeholder, 0, -1) : $placeholder;

                // Named parameter check
                if (array_key_exists($paramName, $parameters)) {
                    return (string) $parameters[$paramName];
                }

                // Indexed parameter check
                if (array_key_exists($index, $parameters)) {
                    return (string) $parameters[$index++];
                }

                // Handle optional parameters
                if ($isOptional) {
                    return '';
                }

                throw new Exception(
                    "Missing required parameter: '{$placeholder}' for URL segment: '{$matches[0]}'"
                );
            },
            $url
        );
    }
}
