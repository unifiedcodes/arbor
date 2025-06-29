<?php

/**
 * Application entry point.
 * 
 * This file serves as the main application bootstrap, handling autoloading,
 * application initialization, environment configuration, and HTTP request handling.
 * 
 * @package Arbor
 * 
 */

use Arbor\Autoloader;
use Arbor\bootstrap\App;
use Arbor\http\Response;

/**
 * Require the Autoloader class
 */
require_once '../Arbor/Autoloader.php';

/**
 * Initialize the autoloader with the root path
 * 
 * Creates an instance of the custom autoloader that will locate and load
 * class files on demand. The path parameter specifies the application's
 * root directory relative to the current file, allowing the autoloader
 * to resolve class namespaces to file paths correctly.
 * 
 * @var Autoloader $autoloader Handles class autoloading for the application
 */
$autoloader = new Autoloader('../');

/**
 * Bootstrap the application
 * 
 * Initializes the application with configuration files, sets the environment,
 * and applies specific app configurations based on context.
 * 
 * @var App $app The main application instance
 */
$app = (new App())
    /**
     * Set the base configuration directory path
     * 
     * This method defines where the application should look for its
     * general configuration files, establishing the foundation for
     * the application's configuration system.
     */
    ->withConfig('../configs/')

    /**
     * Set the application environment to 'development'
     * 
     * Environment-specific settings will be loaded based on this value.
     * In production, this would be changed to 'production' to enable
     * optimization, caching, and disable debug features.
     */
    ->onEnvironment('development')

    /**
     * Load app-specific application configuration
     * 
     * Applies settings specific to the app module installed in this project..
     * potentially including admin routes, access controls, and app-specific services providers.
     */
    ->useAppConfig('admin/configs/app.php')
    ->useAppConfig('web/configs/app.php')

    /**
     * Complete initialization and boot the application
     * 
     * Finalizes the configuration process, initializes services, connects to databases,
     * sets up dependency injection containers, and prepares the application to handle requests.
     * the final step in the bootstrap chain.
     */
    ->boot();

/**
 * Process the incoming HTTP request and generate a response
 * 
 * This method routes the HTTP request to the appropriate controller,
 * executes required middleware, processes the request, and constructs
 * the response object that will be sent back to the client.
 * 
 * @var Response $response The HTTP response to be sent back to the client
 */
$response = $app->handleHTTP();

/**
 * Send the response to the client
 * 
 * Finalizes the HTTP response by setting headers, status code,
 * and sending the response body to the client. This is the last
 * step in the request-response lifecycle.
 */
$response->send();
