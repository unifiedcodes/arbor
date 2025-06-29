<?php

/**
 * Service Provider Configuration
 * 
 * ------------------------------------------------------------------------
 * 
 * This configuration array defines the service providers that will be
 * automatically loaded and registered during application bootstrap.
 * Service providers are responsible for binding services into the
 * application's dependency injection container.
 * 
 */

return [
    /**
     * Alias Provider
     * 
     * Responsible for registering class aliases within the application.
     * This provider maps shortened or alternative names to fully-qualified
     * class names, enabling more concise syntax throughout the codebase.
     */
    'providers\AliasProvider',
    
    /**
     * Request Provider
     * 
     * Handles registration and configuration of HTTP request processing services.
     * This provider sets up request parsing, validation, and makes request
     * data available throughout the application in a standardized format.
     */
    'providers\RequestProvider',
];