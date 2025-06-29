<?php

/**
 * Database Connection Configuration
 * 
 * ------------------------------------------------------------------------
 * 
 * This configuration array defines parameters for database connectivity
 * within the application. It controls connection pooling behavior,
 * retry mechanisms, and connection credentials for different database
 * environments.
 * 
 */

return [
    /**
     * Connection Pool Settings
     * 
     * Controls how the application manages a pool of database connections
     * for improved performance and reliability.
     */
    'pool' => [
        /**
         * Maximum number of concurrent connections allowed in the pool.
         * Helps prevent resource exhaustion while maintaining performance
         * under heavy load conditions.
         */
        'maxConnections' => 10,
        
        /**
         * Maximum number of connection retry attempts before failing.
         * Application will attempt to reconnect this many times when
         * database connections are interrupted or fail to establish.
         */
        'maxRetries' => 10,
        
        /**
         * Delay between retry attempts in milliseconds.
         * Implements exponential backoff strategy to prevent overwhelming
         * the database server during recovery from outages.
         */
        'retryDelay' => 1000,
    ],
    
    /**
     * Database Connection Profiles
     * 
     * Defines various database connections available to the application.
     * Multiple connections can be configured for different databases
     * or for read/write splitting scenarios.
     */
    'connections' => [
        /**
         * Default database connection profile.
         * Used when no specific connection is requested by the application.
         * Contains essential credentials and connection parameters.
         */
        'default' => [
            /**
             * Name of the database to connect to.
             * Specifies which database on the server will be used.
             */
            'databaseName' => 'greenrate',
            
            /**
             * Database server hostname or IP address.
             * For local development environments, typically set to localhost.
             */
            'host' => 'localhost',
            
            /**
             * Database user account username.
             * This account should have appropriate permissions for the
             * operations performed by the application.
             */
            'username' => 'root',
            
            /**
             * Database user account password.
             * Empty string in development environments; should be secured
             * with a strong password in staging and production.
             */
            'password' => ''
        ]
    ]
];