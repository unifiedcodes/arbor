<?php

/**
 * Application Bootstrap Configuration
 * 
 * ------------------------------------------------------------------------
 * 
 * This configuration file initializes fundamental constants and settings
 * used throughout the application. It establishes core path references,
 * defines global URIs, and provides a structure for registering application
 * modules during the bootstrap process.
 * 
 */

/**
 * Root Directory Constant
 * 
 * Defines the absolute filesystem path to the application's root directory.
 * Used as the base reference point for all relative file paths within
 * the application.
 * 
 */
define('ROOT_DIR', realpath('../'));

/**
 * Base URI Constant
 * 
 * Defines the base URL path for the application.
 * All application URLs will be built relative to this URI,
 * forming complete URLs for routing and link generation.
 * 
 */
define('BASE_URI', 'localhost/arbor/');


/**
 * HTTP Protocol Detection
 * 
 * Determines the appropriate protocol (HTTP or HTTPS) based on the server 
 * environment. This value is used when generating absolute URLs, ensuring 
 * that the correct scheme is applied for secure or non-secure requests.
 * 
 */
define('HTTP_PROTOCOL', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://');

/**
 * Debug Mode Toggle
 * 
 * Enables or disables debug mode for the application. When set to true, 
 * the application may display detailed error messages, enable verbose logging,
 * or activate development tools. This should be set to false in production 
 * environments to enhance security and performance.
 * 
 */
define('IS_DEBUG', true);



return [
    /**
     * Root Directory Configuration
     * 
     * Makes the ROOT_DIR constant available as a configuration value.
     * This allows subsystems that only have access to configuration
     * arrays to reference the application's root directory.
     */
    'root_dir' => ROOT_DIR,


    /**
     * Global Base URI Configuration
     * 
     * Makes the BASE_URI constant available as a configuration value.
     * This allows subsystems that only have access to configuration
     * arrays to reference the application's base URL.
     */
    'global_base_uri' => BASE_URI,

    /**
     * Application Modules Registry
     * 
     * Defines the application modules to be loaded during bootstrap.
     * This array is intended to be populated by public/index.php
     * with the specific modules required for the current environment.
     */
    'apps' => [
        // included by public/index.php while bootstrapping
    ]
];
