<?php

namespace Arbor\bootstrap;

use Exception;
use Arbor\config\Configurator;

/**
 * AppConfigScope class
 * 
 * Handles application configuration scoping based on request URIs.
 * Allows for multi-application setups with different configuration directories.
 * 
 * This class is responsible for:
 * - Loading application configurations from multiple files
 * - Determining which application should handle a request based on URI prefixes
 * - Setting up the appropriate configuration scope for the matched application
 * - Merging environment-specific configurations
 * 
 * Example usage:
 * ```php
 * $scope = new AppConfigScope($configurator, '/app', '/var/www', 'production');
 * $scope->appConfigByFiles(['app1' => 'configs/app1.php', 'app2' => 'configs/app2.php']);
 * $scope->scope('api'); // Matches application with 'api' URI prefix
 * ```
 * 
 * @package Arbor\bootstrap
 */
class AppConfigScope
{
    /**
     * Application configurator instance
     * 
     * Used for getting/setting configuration values and merging config directories.
     * This is the main interface for configuration management.
     * 
     * @var Configurator
     */
    protected Configurator $configurator;

    /**
     * Current environment (e.g., 'production', 'development', 'testing')
     * 
     * Used to load environment-specific configuration files.
     * Environment determines which config variants to load (e.g., database.production.php).
     * 
     * @var string
     */
    protected string $environment;

    /**
     * Root URI for the application
     * 
     * The base URI path for the entire application suite.
     * Injected from configuration using the 'root.uri' key.
     * Example: '/myapp' or '' for root-level deployment.
     * 
     * @var string
     */
    protected string $rootUri;

    /**
     * Root directory of the application
     * 
     * Absolute filesystem path to the application root.
     * Injected from configuration using the 'root.dir' key.
     * Used as base path for resolving relative config file paths.
     * Example: '/var/www/html' or '/home/user/project'
     * 
     * @var string
     */
    protected string $rootDir;

    /**
     * Application configuration array
     * 
     * Contains all installed application configurations loaded from files.
     * Structure: [app_name => app_config_array]
     * Each app config should contain at minimum:
     * - 'uri_prefix': string - The URI prefix that routes to this app
     * - 'configs_dir': string - Relative path to app's config directory
     * 
     * @var array<string, array<string, mixed>>
     */
    protected array $installedApps;

    /**
     * Constructor for AppConfigScope
     * 
     * Initializes the configuration scope handler with required dependencies.
     * Uses dependency injection with configuration value attributes for
     * automatic injection of root URI and directory from the configurator.
     * 
     * @param Configurator $configurator   The configurator instance for config management
     * @param string       $rootUri       Global Root URI from config (injected via ConfigValue attribute)
     * @param string       $rootDir       Root directory from config (injected via ConfigValue attribute)  
     * @param string       $env           Current environment name (e.g., 'production', 'development')
     * 
     * @throws Exception If configurator is null or invalid
     */
    public function __construct(
        Configurator $configurator,

        string $rootUri,

        string $rootDir,

        string $env
    ) {
        $this->configurator = $configurator;
        $this->environment = $env;

        $this->rootDir = $rootDir;
        $this->rootUri = $rootUri;
    }

    /**
     * Load application configurations from files
     * 
     * Reads configuration files for multiple applications and stores them
     * in the configurator under the 'installed_apps' key. Each file should
     * return a PHP array containing the application configuration.
     * 
     * File paths are resolved relative to the root directory and normalized
     * to ensure consistent directory separators across platforms.
     * 
     * Expected file structure:
     * ```php
     * // app1.php
     * return [
     *     'uri_prefix' => 'api',
     *     'configs_dir' => 'apps/api/config',
     *     'name' => 'API Application',
     *     // ... other app-specific config
     * ];
     * ```
     * 
     * @param array<string, string> $files   Associative array: [app_name => relative_file_path]
     *                                       Example: ['api' => 'configs/api.php', 'web' => 'configs/web.php']
     * @return void
     * 
     * @throws Exception If a configuration file doesn't exist or returns invalid data
     * @throws Error If require_once fails or file contains syntax errors
     */
    public function appConfigByFiles(array $files): void
    {
        // Early return if no files provided - avoids unnecessary processing
        if (empty($files)) {
            return;
        }

        /** @var array<string, array<string, mixed>> $appConfigs */
        $appConfigs = [];

        // Process each configuration file
        foreach ($files as $appName => $file) {
            // Normalize the file path by combining root directory with relative path
            // rtrim removes trailing directory separators to prevent double separators
            $file_path = rtrim(normalizeDirPath($this->rootDir . DIRECTORY_SEPARATOR . $file), DIRECTORY_SEPARATOR);

            // Load configuration array from file
            // require_once ensures file is loaded only once and returns the array
            $appConfigs[$appName] = require_once($file_path);
        }

        // Store all loaded app configurations in the global configurator
        $this->installedApps = $appConfigs;
        $this->configurator->set('installed_apps', $appConfigs);
    }

    /**
     * Determine and set application scope based on application key
     * 
     * This is the main method that:
     * 1. Retrieves all installed application configurations
     * 2. Finds the application matching the provided key
     * 3. Sets the matched application as the current scope
     * 4. Merges the application's specific configuration directory
     * 
     * The process ensures that the correct application configuration is loaded
     * and available for the current request context.
     * 
     * @param string $app_key   Application key to match against URI prefixes
     *                         Should correspond to an app's 'uri_prefix' value
     *                         Example: 'api', 'admin', 'web', etc.
     * 
     * @return void
     * 
     * @throws Exception When no configured apps are found in the configurator
     * @throws Exception When no application matches the provided key (via findApp())
     * 
     * @see findApp() For the application matching logic
     * @see setAppScope() For setting the current application scope
     * @see mergeConfig() For merging application-specific configurations
     */
    public function scope(string $app_key): void
    {
        // Validate that applications are configured
        if (empty($this->installedApps)) {
            throw new Exception("Configuration error: 'installed_apps' key is missing or contains an empty app list.");
        }

        // Find the application that matches the provided key
        $found_app = $this->findApp($app_key);

        // Set the found application as the current scope
        $this->setAppScope($found_app);

        // Load and merge the application's specific configuration files
        $this->mergeConfig($found_app['configs_dir']);
    }

    /**
     * Merge configuration files from the application config directory
     * 
     * Loads all configuration files from the specified application's config directory
     * and merges them with the existing configuration. This allows each application
     * to have its own set of configuration files (database, cache, etc.) that
     * override or extend the global configuration.
     * 
     * The merge process is environment-aware, meaning it will load environment-specific
     * variants of configuration files (e.g., database.production.php, cache.development.php).
     * 
     * @param string $configs_dir   Relative path to the application's configuration directory
     *                             Example: 'apps/api/config' or 'modules/admin/config'
     * 
     * @return void
     * 
     * @throws Exception If the configuration directory doesn't exist
     * @throws Exception If configuration files contain invalid PHP or return non-arrays
     */
    protected function mergeConfig(string $configs_dir): void
    {
        // Build absolute path to the application's config directory
        // Combines the global root directory with the app-specific config directory
        $configs_dir = $this->rootDir . DIRECTORY_SEPARATOR . $configs_dir;

        // Delegate to configurator to merge all config files from the directory
        // The environment parameter ensures environment-specific configs are loaded
        $this->configurator->mergeByDir($configs_dir, $this->environment);
    }

    /**
     * Set the current application scope in the configurator
     * 
     * Stores the matched application configuration under the 'app' key in the
     * global configurator. This makes the current application's configuration
     * accessible throughout the application lifecycle.
     * 
     * After this method is called, other parts of the application can retrieve
     * the current app config using: $configurator->get('app')
     * 
     * @param array<string, mixed> $app   Complete application configuration array
     *                                   Should contain at minimum:
     *                                   - 'uri_prefix': string
     *                                   - 'configs_dir': string
     *                                   - Additional app-specific configuration
     * 
     * @return void
     */
    protected function setAppScope(array $app): void
    {
        // Store the current application configuration globally
        $this->configurator->set('app', $app);
    }

    /**
     * Find the application configuration based on URI prefix
     * 
     * Searches through all installed applications to find one whose 'uri_prefix'
     * matches the provided app key. Implements fallback logic for default applications.
     * 
     * Matching logic:
     * 1. First, looks for exact match on 'uri_prefix'
     * 2. If no exact match, falls back to default app (uri_prefix = '' or 'default')
     * 3. If no default app exists, throws exception
     * 
     * This allows for flexible routing where:
     * - Specific prefixes route to specific apps (e.g., 'api' -> API app)
     * - Unmatched requests fall back to a default app (e.g., main website)
     * 
     * @param string $app_key   Application key to match against URI prefixes
     *                         Typically derived from request URI parsing
     *                         Example: 'api', 'admin', 'mobile', etc.
     * 
     * @return array<string, mixed>   The complete matched application configuration array
     *                               Contains all configuration data for the matched app
     * 
     * @throws Exception When no matching app is found and no default app is configured
     *                  Error message includes the app_key for debugging
     */
    protected function findApp(string $app_key): array
    {
        /** @var array<string, mixed>|null $defaultApp */
        $defaultApp = null;

        // Iterate through all installed applications
        foreach ($this->installedApps as $app) {
            // Check for exact URI prefix match
            if ($app['uri_prefix'] === $app_key) {
                return $app; // Return immediately on exact match
            }

            // Store potential default/fallback application
            // Default apps have empty string or explicit 'default' as uri_prefix
            if (
                $app['uri_prefix'] === ''
                || $app['uri_prefix'] === 'default'
            ) {
                $defaultApp = $app; // Store for potential fallback use
            }
        }

        // If no exact match found, try to use default application
        if ($defaultApp !== null) {
            return $defaultApp;
        }

        // No match and no default - this is a configuration error
        throw new Exception("No matching app found for prefix: '$app_key'");
    }
}
