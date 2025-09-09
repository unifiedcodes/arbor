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


if (!function_exists('relativeURL')) {
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
    function relativeURL(string $path, array $params = []): string
    {
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



if (!function_exists('routeURL')) {
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
    function routeURL(string $name, array $params = []): string
    {
        return Route::url($name, $params);
    }
}


if (!function_exists('assets')) {
    /**
     * Generate a full asset URL using the asset directory defined in config.
     * 
     * Constructs URLs for application assets (CSS, JavaScript, images, etc.) by
     * combining the base URI with the configured assets directory path.
     * 
     * The base URI is retrieved from 'root.uri' config (defaults to '/'), and
     * the assets directory is retrieved from 'app.assets_dir' config (defaults 
     * to 'assets'). Both values are normalized to prevent path issues.
     * 
     * This function is essential for creating proper asset URLs that respect
     * the application's directory structure and configuration, ensuring assets
     * are loaded correctly regardless of where the application is installed.
     * 
     * Example: 
     * // Assuming Config::get('root.uri') = '/sandbox' and Config::get('app.assets_dir') = 'assets'
     * assets('css/style.css') 
     * → /sandbox/assets/css/style.css
     * 
     * assets('js/app.min.js')
     * → /sandbox/assets/js/app.min.js
     *
     * @param string $path The relative path to the asset within the assets directory
     * @return string The complete URL to the asset file
     */
    function assets(string $path): string
    {
        $base = rtrim(Config::get('root.uri', '/'), '/');
        $assetDir = trim(Config::get('app.assets_dir', 'assets'), '/');
        $path = ltrim($path, '/');

        return "{$base}/{$assetDir}/{$path}";
    }
}


if (!function_exists('statics')) {
    /**
     * Generate a full static URL using the static directory defined in config.
     * 
     * Creates URLs for static files (uploads, user-generated content, etc.) by
     * combining the base URI with the configured static files directory.
     * 
     * The function uses a two-level configuration approach:
     * 1. First tries 'app.statics_dir' from config
     * 2. Falls back to 'root.statics_dir' (defaults to 'statics')
     * 
     * This allows for flexible static file directory configuration at both
     * application and system levels. The base URI comes from 'root.uri'
     * configuration with a fallback to '/'.
     * 
     * Static files typically include user uploads, generated files, cached
     * content, or any files that are not part of the core application assets.
     * 
     * Example: 
     * // Assuming Config::get('root.uri') = '/sandbox' and static dir = 'static'
     * statics('img/logo.png') 
     * → /sandbox/static/img/logo.png
     * 
     * statics('uploads/document.pdf')
     * → /sandbox/static/uploads/document.pdf
     *
     * @param string $path The relative path to the static file within the statics directory
     * @return string The complete URL to the static file
     */
    function statics(string $path): string
    {
        $base = rtrim(Config::get('root.uri', '/'), '/');
        $root_statics_dir = Config::get('root.statics_dir', 'statics');
        $staticDir = trim(Config::get('app.statics_dir', $root_statics_dir), '/');
        $path = ltrim($path, '/');

        return "{$base}/{$staticDir}/{$path}";
    }
}
