<?php

/**
 * 
 * Admin Panel Configuration
 * 
 * ------------------------------------------------------------------------
 * 
 * This configuration array defines the fundamental parameters and paths
 * that control the admin panel's behavior and structure. It establishes
 * the admin interface identity, directory structures, URI handling, and
 * operational mode.
 * 
 * Note: Several constants "BASE_URI, ROOT_DIR, HTTP_PROTOCOL" are inherited 
 * from the main application configuration at /configs/app.php
 * 
 */

return [
    /**
     * Admin panel name/title used for browser tabs
     */
    'title' => 'ArborCMS',

    /**
     * Absolute filesystem path to the application's root directory.
     * This constant is typically defined in a bootstrap file and
     * serves as the reference point for all directory paths.
     */
    'root_dir' => ROOT_DIR,

    /**
     * Base URI for the admin panel defining the root URL path.
     * Appends 'admin' to the main application BASE_URI to create
     * a separate URL space for administrative functions.
     */
    'global_base_uri' => BASE_URI,

    /**
     * Primary base URI for the app level usage.
     * Combines the application's base URI with the admin prefix to create
     * the foundation URL path for all admin-specific pages and resources.
     * This value is used throughout the admin system for URL generation.
     */
    'base_uri' => BASE_URI . 'admin',

    /**
     * HTTP protocol used for URLs (http:// or https://)
     * Determines the protocol used when constructing absolute URLs 
     * for assets, uploads, and other resources.
     */
    'http_protocol' => HTTP_PROTOCOL,

    /**
     * Complete URL path to static resources for the main application
     * Contains shared resources like images, stylesheets, and JavaScript files
     * that are used by both public and admin interfaces.
     */
    'statics_url' => HTTP_PROTOCOL . BASE_URI . 'static/',

    /**
     * Complete URL path to user-uploaded files
     * Provides a consistent way to reference user-uploaded content
     * across the application for display or manipulation.
     */
    'uploads_url' => HTTP_PROTOCOL . BASE_URI . 'uploads/',

    /**
     * Complete URL path to admin-specific assets
     * Contains admin panel UI resources like CSS frameworks,
     * JavaScript libraries, and custom admin interface components.
     */
    'assets_url' => HTTP_PROTOCOL . BASE_URI . 'admin/assets/',

    /**
     * URI prefix specifically for admin routes.
     * This ensures all admin panel URLs begin with this identifier,
     * creating a clear separation from public-facing routes.
     */
    'uri_prefix' => 'admin',

    /**
     * Directory where admin-specific configuration files are stored.
     * These may include user permissions, dashboard settings, UI options,
     * and other admin-exclusive configurations.
     */
    'config_dir' => ROOT_DIR . '/admin/configs/',

    /**
     * Directory containing admin route definitions that map URLs to controllers.
     * These files define the admin panel's URL structure and determine
     * which code handles specific administrative requests.
     */
    'routes_dir' => ROOT_DIR . '/admin/routes/',

    /**
     * Directory where user-uploaded files are stored.
     * Shared with the main application, this directory contains all
     * files uploaded through both public and admin interfaces.
     */
    'uploads_dir' => ROOT_DIR . '/uploads/',

    /**
     * Admin panel operating mode flag.
     * - When true: Enables verbose error reporting, logging, and debugging tools
     * - When false: Production mode with minimal error exposure to administrators
     * Should be set to false in production environments for security.
     */
    'isDebug' => defined('IS_DEBUG') ? constant('IS_DEBUG') : false,

    /**
     * Directory containing admin view templates
     * These templates define the HTML structure and presentation
     * of the admin panel interface and its components.
     */
    'views_dir' => ROOT_DIR . '/admin/views/',

    /**
     * Directory containing static files served directly by the webserver
     * Includes public-facing resources like images, stylesheets, fonts
     * and client-side scripts that don't require processing.
     */
    'statics_dir' => ROOT_DIR . '/static/',
];
