<?php

namespace Arbor\bootstrap;


use Arbor\container\Container;
use Arbor\config\Config;
use Arbor\http\HttpKernel;
use Arbor\http\RequestFactory;
use Arbor\http\Response;
use Arbor\router\Router;
use Exception;


/**
 * Class App
 *
 * The main application class that extends the DI container and manages
 * configuration, service providers, and HTTP request handling.
 *
 * @package Arbor\bootstrap
 */

class App extends Container
{
    /**
     * The singleton instance of the App.
     *
     * @var App|null
     */
    protected static ?App $instance = null;

    /**
     * The directory where configuration files are located.
     *
     * @var string
     */
    protected string $configDir;

    /**
     * The current environment (e.g., 'development', 'production').
     *
     * @var string|null
     */
    protected ?string $environment = null;

    /**
     * Providers to be registered (merged from config and inline).
     *
     * @var array
     */
    protected array $providersToRegister = [];

    /**
     * Inline providers explicitly passed.
     *
     * @var array
     */
    protected array $inlineProviders = [];

    /**
     * Flag to determine whether inline providers should be merged
     * with providers from config.
     *
     * @var bool
     */
    protected bool $mergeProviders = false;

    /**
     * The Config instance.
     *
     * @var Config
     */
    public Config $config;

    /**
     * App constructor.
     *
     * Initializes the container and sets the singleton instance.
     */
    public function __construct()
    {
        parent::__construct($this);

        // Set the static instance if not already set.
        if (self::$instance === null) {
            self::$instance = $this;
        }
    }

    /**
     * Retrieve the singleton App instance.
     *
     * @return App
     * @throws Exception if App has not been instantiated.
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
     * @param string $configDir Path to the configuration directory.
     * @return $this
     */
    public function withConfig(string $configDir): self
    {
        $this->configDir = $configDir;
        return $this;
    }

    /**
     * Set the application environment.
     *
     * @param string $env Environment name (e.g., 'development').
     * @return $this
     */
    public function onEnvironment(string $env): self
    {
        $this->environment = $env;
        return $this;
    }

    /**
     * Set inline providers with an option to merge with config providers.
     *
     * @param array $providers Array of provider class names.
     * @param bool $merge Whether to merge with providers from config.
     * @return $this
     */
    public function withProviders(array $providers = [], bool $merge = false): self
    {
        $this->inlineProviders = $providers;
        $this->mergeProviders = $merge;
        return $this;
    }

    /**
     * Boot the application by loading configuration and providers.
     *
     * @return $this
     * @throws Exception if configuration directory is not specified.
     */
    public function boot(): self
    {
        // Ensure configuration directory is set.
        if (!isset($this->configDir)) {
            throw new Exception("Configuration directory not specified.");
        }

        $this->loadConfiguration();
        $this->loadProviders();

        return $this;
    }

    /**
     * Load the configuration and bind it as a singleton in the container.
     *
     * @return void
     */
    protected function loadConfiguration(): void
    {
        // Bind the Config instance as a singleton.
        $this->singleton(Config::class, function (): Config {
            return new Config($this->configDir, $this->environment);
        });

        // Retrieve and set the Config instance.
        $this->config = $this->make(Config::class);
    }

    /**
     * Load and register providers from both configuration and inline definitions.
     *
     * @return void
     */
    protected function loadProviders(): void
    {
        // Retrieve providers from the configuration.
        $configProviders = $this->config ? $this->config->get('providers', []) : [];

        // Determine which providers to register based on inline settings.
        if ($this->inlineProviders) {
            $this->providersToRegister = $this->mergeProviders
                ? array_merge($configProviders, $this->inlineProviders)
                : $this->inlineProviders;
        } else {
            $this->providersToRegister = $configProviders;
        }

        // Register providers through the container.
        $this->registerProviders($this->providersToRegister);

        // Boot providers.
        $this->bootProviders();
    }


    public function isDebug(): bool
    {
        // this can be extended to create more extensive approach
        // one of that can be overriding with configuration too.
        return $this->environment == 'production' ? false : true;
    }

    /**
     * Handle an incoming HTTP request.
     *
     * @return Response
     */
    public function handleHTTP(): Response
    {
        // create request from Globals.
        $request = $this->resolve(RequestFactory::class)::fromGlobals();


        // Auto Resolve a Router instance from the container.
        $router = $this->make(Router::class);

        // is this debug environment ?
        $isDebug = $this->isDebug();

        // Resolve the Kernel with the Router as a dependency.
        $kernel = $this->make(
            HttpKernel::class,
            [
                'router' => $router,
                'isDebug' => $isDebug,
                'baseURI' => $this->config->get('app.baseURI')
            ]
        );

        // Process the request through the Kernel and return the response.
        return $kernel->handle($request);
    }


    public function getConfig(string $key, mixed $default = null)
    {
        // delegates to configuration...
        return $this->config->get($key, $default);
    }
}
