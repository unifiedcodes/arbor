<?php

namespace Arbor\router;

use Exception;
use Arbor\attributes\ConfigValue;


/**
 * URLBuilder handles dynamic URL construction with parameter replacement
 * 
 * @package Arbor\Router
 */
final class URLBuilder
{
    public function __construct(
        #[ConfigValue('root.uri')]
        protected string $baseURI
    ) {}

    /**
     * @var array<string, string> Named route registry
     */
    private array $namedRegistry = [];
    private array $pathRegistry = [];

    /**
     * Register a named route path
     * 
     * @param string $name Unique route identifier
     * @param string $path URL pattern with {placeholders}
     * @return void
     */
    public function add(string $name, string $path, string $verb): void
    {
        $verb = strtoupper($verb);

        if (isset($this->namedRegistry[$name])) {
            throw new Exception("Name '{$name}' for '{$verb}: {$path}' is already in use");
        }

        if (isset($this->pathRegistry[$verb][$path])) {
            throw new Exception("Duplicate route for {$verb} {$path}");
        }

        $this->namedRegistry[$name] = $path;
        $this->pathRegistry[$verb][$path] = $name;
    }


    public function getRouteName(string $path, string $verb): string
    {
        return $this->pathRegistry[$verb][$path] ?? '';
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
    public function getAbsoluteURL(string $routeName, array $parameters = []): string
    {
        $relativeUrl = $this->getRelativeURL($routeName, $parameters);
        $baseURI = rtrim($this->baseURI, '/');
        $relativeUrl = ltrim($relativeUrl, '/');

        if ($relativeUrl === '') {
            return $baseURI ?: '/';
        }

        return $baseURI . '/' . $relativeUrl;
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

        $result = preg_replace_callback(
            '/\{([a-zA-Z0-9_]+)([?*]+)?\}/',
            function (array $matches) use ($parameters, &$index): string {
                $paramName = $matches[1];
                $mods = $matches[2] ?? '';

                $isOptional = str_contains($mods, '?');
                $isGreedy   = str_contains($mods, '*');

                // named param
                if (array_key_exists($paramName, $parameters)) {
                    return (string) $parameters[$paramName];
                }

                // indexed param
                if (array_key_exists($index, $parameters)) {
                    return (string) $parameters[$index++];
                }

                // optional & greedy both drop if missing
                if ($isOptional || $isGreedy) {
                    return '';
                }

                // required fails
                throw new \Exception("Missing parameter: '{$paramName}'");
            },
            $url
        );

        $result = normalizeURLSlashes($result);

        return $result === '' ? '/' : $result;
    }
}
