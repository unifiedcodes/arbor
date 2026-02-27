<?php

use Arbor\facades\Config;
use Arbor\facades\Route;

/**
 * URL Helper Functions for Arbor Framework
 * 
 * This file contains utility functions for generating various types of URLs
 * within the Arbor framework. These functions provide a convenient way to
 * create URLs for different purposes including relative paths, asset paths,
 * static file paths, and named routes.
 * 
 * All functions are conditionally defined to prevent redeclaration errors
 * and integrate with Arbor's Config and Route facades for configuration
 * and routing functionality.
 */

if (!function_exists('url')) {
    /**
     * Generate a relative URL with optional query parameters (without base URI).
     * 
     * Creates a simple relative URL by combining a path with query parameters.
     * This function does NOT prepend any base URI or configuration values,
     * making it suitable for creating portable relative URLs.
     * 
     * The path is normalized by removing trailing slashes, and query parameters
     * are properly encoded using http_build_query().
     * 
     * Example:
     * url('blog/view', ['id' => 1])
     * → blog/view?id=1
     * 
     * url('admin/dashboard')
     * → admin/dashboard
     *
     * @param string $path The relative path for the URL
     * @param array $params Optional associative array of query parameters
     * @return string The generated relative URL with query string if parameters provided
     */
    function url(string $path, array $params = []): string
    {
        $url = rtrim($path, '/');

        if (!empty($params)) {
            $query = http_build_query($params);
            $url .= '?' . $query;
        }

        return $url;
    }
}


if (!function_exists('relativeUrl')) {
    /**
     * Generate a URL using the configured base URI and optional query parameters.
     * 
     * Constructs a URL by combining the application's base URI (from configuration)
     * with the provided path. The base URI is retrieved from the 'root.uri' config
     * key, with a fallback to '/' if not configured.
     * 
     * This function is ideal for creating URLs that need to respect the application's
     * installation directory or subdirectory structure. The path is normalized by
     * removing leading slashes to prevent double slashes in the final URL.
     * 
     * Uses `Config::get('root.uri')` as base path.
     *
     * Example:
     * // Assuming Config::get('root.uri') returns '/sandbox'
     * relativeURL('blog/view', ['id' => 1])
     * → /sandbox/blog/view?id=1
     * 
     * relativeURL('api/users')
     * → /sandbox/api/users
     *
     * @param string $path The path to append to the base URI
     * @param array $params Optional associative array of query parameters
     * @return string The generated URL with base URI and query string if parameters provided
     */
    function relativeUrl(string $path, array $params = []): string
    {
        if (!class_exists(Config::class)) {
            throw new RuntimeException('Config facade not available.');
        }

        $base = Config::get('root.uri', '/');
        $path = ltrim($path, '/');

        $url = $base . '/' . $path;

        if (!empty($params)) {
            $query = http_build_query($params);
            $url .= '?' . $query;
        }

        return $url;
    }
}



if (!function_exists('routeUrl')) {
    /**
     * Generate a URL from a named route using Arbor's Route facade.
     * 
     * Creates URLs by resolving named routes through the Arbor routing system.
     * This function delegates URL generation to the Route facade's url() method,
     * which handles route resolution, parameter substitution, and URL construction
     * according to the defined route patterns.
     * 
     * Named routes typically use the '@' prefix convention in Arbor framework.
     * Route parameters are substituted into the route pattern as defined in
     * the routing configuration.
     * 
     * This is the preferred method for generating URLs to application routes
     * as it provides decoupling from the actual URL structure and automatic
     * handling of route parameter injection.
     *
     * Example: 
     * // Assuming route '@user.profile' is defined as '/user/profile/{id}'
     * routeURL('@user.profile', ['id' => 12]) 
     * → /sandbox/user/profile/12
     * 
     * routeURL('@home')
     * → /sandbox/
     *
     * @param string $name Route name (e.g., @user.profile, @admin.dashboard)
     * @param array $params Route parameters for substitution (e.g., ['id' => 12])
     * @return string The generated URL for the named route
     */
    function routeUrl(string $name, array $params = []): string
    {
        if (!class_exists(Route::class)) {
            throw new RuntimeException('Route facade not available.');
        }

        return Route::url($name, $params);
    }
}
