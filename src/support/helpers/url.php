<?php

use Arbor\facades\Config;
use Arbor\facades\Route;


if (!function_exists('url')) {
    /**
     * Generate a relative URL with optional query parameters (without base URI).
     * 
     * Example:
     * url('blog/view', ['id' => 1])
     * → blog/view?id=1
     *
     * @param string $path
     * @param array $params
     * @return string
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
     * Uses `Config::get('root.uri')` as base path.
     *
     * Example:
     * relativeURL('blog/view', ['id' => 1])
     * → /sandbox/blog/view?id=1
     *
     * @param string $path
     * @param array $params
     * @return string
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
     * Example: routeURL('@user.profile', ['id' => 12]) → /sandbox/user/profile/12
     *
     * @param string $name Route name (e.g., @user.profile)
     * @param array $params Route parameters (e.g., ['id' => 12])
     * @return string
     */
    function routeURL(string $name, array $params = []): string
    {
        return Route::url($name, $params);
    }
}


if (!function_exists('assets')) {
    /**
     * Generate a full asset URL using the asset directory defined in config.
     * Example: asset('css/style.css') → /sandbox/assets/css/style.css
     *
     * @param string $path
     * @return string
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
     * Example: static('img/logo.png') → /sandbox/static/img/logo.png
     *
     * @param string $path
     * @return string
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
