<?php

namespace Arbor\bootstrap;

use Exception;
use Arbor\bootstrap\URLResolver;
use Arbor\bootstrap\AppConfigScope;
use Arbor\container\ServiceContainer;
use Arbor\config\Configurator;
use Arbor\http\HttpKernel;
use Arbor\http\RequestFactory;
use Arbor\http\Response;
use Arbor\router\Router;
use Arbor\http\ServerRequest;
use Arbor\support\Helpers;
use Arbor\facades\Facade;

/**
 * Class App
 *
 * The main application class that serves as the central bootstrap and service container
 * for the Arbor framework. This class implements the Singleton pattern and manages:
 * - Application configuration loading and management
 * - Service provider registration and bootstrapping
 * - HTTP request/response lifecycle
 * - Environment-specific behavior
 * - Dependency injection container
 *
 * The App class follows a fluent interface pattern for configuration and provides
 * centralized access to all framework services through its container.
 *
 * @package Arbor\bootstrap
 * 
 */
class App
{
    /**
     * The singleton instance of the App.
     *
     * Stores the single instance of the App class following the Singleton pattern.
     * This ensures only one App instance exists throughout the application lifecycle.
     *
     * @var App|null The singleton App instance, null if not yet instantiated
     */
    protected static ?App $instance = null;

    /**
     * The directory where configuration files are located.
     *
     * Absolute or relative path to the directory containing configuration files.
     * This directory should contain environment-specific config files.
     *
     * @var string Path to configuration directory
     */
    protected string $configDir;

    /**
     * The current environment (e.g., 'development', 'production').
     *
     * Determines which configuration files to load and affects error reporting,
     * debugging behavior, and other environment-specific settings.
     * Defaults to 'production' for security.
     *
     * @var string|null Current application environment
     */
    protected ?string $environment = 'production';

    /**
     * The root URI for the application.
     *
     * Base URI path for the application, automatically detected from the request
     * or manually configured. Used for URL generation and routing.
     *
     * @var string Root URI path (e.g., '/myapp' or '')
     */
    protected string $rootURI = '';

    /**
     * The Configurator instance.
     *
     * Handles loading, parsing, and accessing configuration values from files.
     * Provides environment-specific configuration management.
     *
     * @var Configurator Configuration manager instance
     */
    protected Configurator $configurator;

    /**
     * The Server Request Instance.
     *
     * Represents the current HTTP request being processed, containing all
     * request data including headers, parameters, body, and server variables.
     *
     * @var ServerRequest Current HTTP request object
     */
    protected ServerRequest $request;

    /**
     * Application-specific configuration files array.
     *
     * Maps application names to their specific configuration file paths.
     * Allows for modular configuration management across different app components.
     *
     * @var array<string, string> Array mapping app names to config file paths
     */
    protected array $appConfigFiles = [];

    /**
     * Dependency injection container instance.
     *
     * Manages service registration, resolution, and lifecycle. Handles both
     * singleton and transient service instances, provider registration, and
     * automatic dependency injection.
     *
     * @var ServiceContainer The DI container instance
     */
    protected ServiceContainer $container;

    /**
     * App constructor.
     *
     * Initializes the dependency injection container and prepares the application
     * for configuration. The constructor is kept minimal as the actual bootstrapping
     * happens in the boot() method.
     *
     * @return void
     */
    public function __construct()
    {
        $this->container = new ServiceContainer();
    }

    /**
     * Retrieve the singleton App instance.
     *
     * Implements the Singleton pattern by returning the single App instance.
     * Throws an exception if the instance hasn't been created yet, ensuring
     * proper initialization order.
     *
     * @return App The singleton App instance
     * @throws Exception If App has not been instantiated yet
     * 
     * @example
     * ```php
     * $app = App::instance();
     * $config = $app->getConfig('database.host');
     * ```
     */
    public static function instance(): App
    {
        if (self::$instance === null) {
            throw new Exception("App instance has not been initialized.");
        }
        return self::$instance;
    }

    /**
     * Set the configuration directory.
     *
     * Specifies the directory containing configuration files. This directory
     * should contain environment-specific config files and will be used by
     * the Configurator to load application settings.
     *
     * @param string $configDir Absolute or relative path to configuration directory
     * @return $this Returns self for method chaining
     * 
     * @example
     * ```php
     * $app->withConfig(__DIR__ . '/config')
     *     ->onEnvironment('development')
     *     ->boot();
     * ```
     */
    public function withConfig(string $configDir): self
    {
        $this->configDir = $configDir;
        return $this;
    }

    /**
     * Set the application environment.
     *
     * Configures the current environment which affects:
     * - Which configuration files are loaded
     * - Error reporting levels
     * - Debug mode behavior
     * - Service provider behavior
     *
     * @param string $env Environment name (e.g., 'development', 'production', 'testing')
     * @return $this Returns self for method chaining
     * 
     * @example
     * ```php
     * $app->onEnvironment('development'); // Enables debug mode and error display
     * $app->onEnvironment('production');  // Disables debug mode and error display
     * ```
     */
    public function onEnvironment(string $env): self
    {
        $this->environment = $env;
        return $this;
    }

    /**
     * Register application-specific configuration file.
     *
     * Associates an application name with a specific configuration file path.
     * This allows for modular configuration where different parts of the application
     * can have their own configuration files.
     *
     * @param string $appName Name/identifier for the application module
     * @param string $config_file Path to the configuration file for this app module
     * @return $this Returns self for method chaining
     * 
     * @example
     * ```php
     * $app->useAppConfig('api', '/path/to/api-config.php')
     *     ->useAppConfig('admin', '/path/to/admin-config.php');
     * ```
     */
    public function useAppConfig(string $appName, string $config_file): self
    {
        $this->appConfigFiles[$appName] = $config_file;
        return $this;
    }

    /**
     * Boot the application by loading configuration and providers.
     *
     * Performs the complete application bootstrapping process:
     * 1. Sets up environment-specific error reporting
     * 2. Loads helper functions
     * 3. Configures the facade container
     * 4. Loads global configuration
     * 5. Detects and sets root URI
     * 6. Loads application-specific configuration
     * 7. Registers and boots service providers
     *
     * This method should be called after all configuration methods and before
     * handling HTTP requests.
     *
     * @return $this Returns self for method chaining
     * @throws Exception If configuration directory is not set or other boot failures
     * 
     * @example
     * ```php
     * $app = new App();
     * $app->withConfig('/path/to/config')
     *     ->onEnvironment('development')
     *     ->boot()
     *     ->handleHTTP();
     * ```
     */
    public function boot(): self
    {
        // Set error display contextually
        $this->environmentContext();

        // load helper functions.
        Helpers::load();

        // since facades share only one instance of container
        // set cotnainer instance to facade,
        // and every facade will be able to access container.
        Facade::setContainer($this->container);

        // load environment specific global configuration
        $this->loadConfig();

        // detect Root URI and insert in config.
        $this->setRootURI();

        // load environment specific app configuration
        $this->scopeConfig();

        // set debug status
        $this->configurator->set('root.is_debug', $this->isDebug());

        // set environment
        $this->configurator->set('root.environment', $this->environment);

        // load service providers
        $this->loadProviders();

        return $this;
    }

    /**
     * Detect and set the root URI for the application.
     *
     * Automatically detects the application's root URI if not manually configured.
     * Uses the URLResolver to analyze the front controller path and determine
     * the appropriate base URI for the application.
     *
     * If 'root.uri' is already set in configuration, this method does nothing,
     * allowing for manual override of auto-detection.
     *
     * @return void
     * 
     * @internal This method is called automatically during boot()
     */
    protected function setRootURI(): void
    {
        if (!empty($this->configurator->get('root.uri'))) {
            // root uri is set by user.
            return;
        }

        $this->rootURI = URLResolver::detectRootUri($this->configurator->get('root.front_controller'));

        $this->configurator->set('root.uri', $this->rootURI);
    }

    /**
     * Configure environment-specific PHP settings.
     *
     * Sets up error reporting and display based on the current environment:
     * - Production: Disables error display and reporting for security
     * - Non-production: Enables full error display and reporting for debugging
     *
     * @return void
     * 
     * @internal This method is called automatically during boot()
     */
    protected function environmentContext(): void
    {
        if ($this->environment === 'production') {
            ini_set('display_errors', '0');
            error_reporting(0);
        } else {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        }
    }

    /**
     * Load the configuration and bind it as a singleton in the container.
     *
     * Creates and configures a Configurator instance with the specified
     * configuration directory and environment. The Configurator is registered
     * as a singleton in the DI container for application-wide access.
     *
     * @return void
     * @throws Exception If configuration directory is not specified
     * 
     * @internal This method is called automatically during boot()
     */
    protected function loadConfig(): void
    {
        // Ensure configuration directory is set.
        if (!isset($this->configDir)) {
            throw new Exception("Configuration directory not specified.");
        }

        // Bind the Config instance as a singleton.
        $this->container->singleton(Configurator::class, function (): Configurator {
            return new Configurator($this->configDir, $this->environment);
        });

        // Retrieve and set the Config instance.
        $this->configurator = $this->container->make(Configurator::class);
    }

    /**
     * Load application-scoped configuration.
     *
     * Handles loading and scoping of application-specific configuration files.
     * Creates an AppConfigScope instance to manage modular configuration
     * and applies configuration based on the detected application key.
     *
     * This allows different applications or modules to have their own
     * configuration while sharing the same framework instance.
     *
     * @return void
     * 
     * @internal This method is called automatically during boot()
     */
    protected function scopeConfig(): void
    {
        $configScope = $this->container->make(AppConfigScope::class, [
            'env' => $this->environment
        ]);

        $configScope->appConfigByFiles($this->appConfigFiles);

        $configScope->scope(URLResolver::getAppKey(
            $this->rootURI
        ));
    }

    /**
     * Load and register service providers.
     *
     * Retrieves service providers from configuration and registers them with
     * the DI container. After registration, all providers are booted to
     * complete their initialization process.
     *
     * Service providers are responsible for binding services into the container
     * and performing any necessary setup operations.
     *
     * @return void
     * 
     * @internal This method is called automatically during boot()
     */
    protected function loadProviders(): void
    {
        // Retrieve providers from the configuration.
        $providers = $this->configurator ? $this->configurator->get('providers', []) : [];

        // Register providers through the container.
        $this->container->registerProviders($providers);

        // Boot providers.
        $this->container->bootProviders();
    }

    /**
     * Determine if the application is in debug mode.
     *
     * Debug mode affects error handling, logging verbosity, and other
     * development-oriented features. The debug status is determined by:
     * 1. Configuration setting 'root.is_debug'
     * 2. Environment (automatically true for non-production environments)
     * 3. Default value (false)
     *
     * @return bool True if debug mode is enabled, false otherwise
     * 
     * @example
     * ```php
     * if ($app->isDebug()) {
     *     // Enable verbose logging
     *     // Show detailed error pages
     * }
     * ```
     */
    protected function isDebug(): bool
    {
        // Default is false.
        $isDebug = false;

        // Override with configuration if set, otherwise use the default.
        $isDebug = $this->configurator->get('root.is_debug', $isDebug);

        // Environment override: false if production.
        return $this->environment == 'production' ? false : $isDebug;
    }

    /**
     * Handle an incoming HTTP request.
     *
     * Processes an HTTP request through the complete request lifecycle:
     * 1. Creates ServerRequest from PHP globals
     * 2. Resolves Router instance from container
     * 3. Determines debug mode
     * 4. Creates HttpKernel with dependencies
     * 5. Processes request through kernel
     * 6. Returns HTTP response
     *
     * This method represents the main entry point for handling web requests.
     *
     * @return Response The HTTP response to send to the client
     * 
     * @example
     * ```php
     * $app = new App();
     * $response = $app->withConfig('/config')
     *                 ->boot()
     *                 ->handleHTTP();
     * $response->send();
     * ```
     */
    public function handleHTTP(): Response
    {
        // building Request Object.
        $requestFactory = $this->container->resolve(RequestFactory::class);

        $this->request = $requestFactory->fromGlobals();

        // Auto Resolve a Router instance from the container.
        $router = $this->container->make(Router::class);

        // Resolve the Kernel with the Router as a dependency.
        $kernel = $this->container->make(
            HttpKernel::class,
            [
                'router' => $router,
                'isDebug' => $this->getConfig('root.is_debug')
            ]
        );

        $kernel->useMiddlewares($this->getConfig('middlewares', []));

        // Process the request through the Kernel and return the response.
        return $kernel->handle($this->request);
    }

    /**
     * Get configuration value(s).
     *
     * Retrieves configuration values using dot notation for nested arrays.
     * If no key is provided, returns all configuration data.
     * If key doesn't exist, returns the provided default value.
     *
     * @param string|null $key Configuration key in dot notation (e.g., 'database.host')
     * @param mixed $default Default value to return if key doesn't exist
     * @return mixed Configuration value, all config data, or default value
     * 
     * @example
     * ```php
     * $dbHost = $app->getConfig('database.host', 'localhost');
     * $allConfig = $app->getConfig(); // Returns entire config array
     * $timeout = $app->getConfig('api.timeout', 30);
     * ```
     */
    public function getConfig(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->configurator->all();
        }

        // delegates to configuration...
        return $this->configurator->get($key, $default);
    }

    /**
     * Get the dependency injection container instance.
     *
     * Provides access to the application's service container for manual
     * service resolution, binding, or container operations outside of
     * the normal dependency injection flow.
     *
     * @return ServiceContainer The DI container instance
     * 
     * @example
     * ```php
     * $container = $app->container();
     * $service = $container->make(MyService::class);
     * $container->bind(Interface::class, Implementation::class);
     * ```
     */
    public function container(): ServiceContainer
    {
        return $this->container;
    }
}
