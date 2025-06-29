<?php

namespace Arbor\bootstrap;


use Exception;
use Arbor\bootstrap\AppConfigScope;
use Arbor\container\Container;
use Arbor\config\Configurator;
use Arbor\http\HttpKernel;
use Arbor\http\RequestFactory;
use Arbor\http\Response;
use Arbor\router\Router;
use Arbor\facades\Config;
use Arbor\http\ServerRequest;
use Arbor\support\Helpers;


/**
 * Class App
 *
 * The main application class that extends the DI container and manages
 * configuration, service providers, and HTTP request handling.
 *
 * @package Arbor\bootstrap
 * 
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
    protected ?string $environment = 'production';


    /**
     * The Config instance.
     *
     * @var Configurator
     */
    protected Configurator $configurator;


    protected ServerRequest $request;

    protected array $appConfigFiles = [];

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


    public function useAppConfig(string $config_file): self
    {
        $this->appConfigFiles[] = $config_file;

        return $this;
    }


    /**
     * Boot the application by loading configuration and providers.
     *
     * @return $this
     * @throws Exception
     */
    public function boot(): self
    {
        // Set error display contextually
        $this->environmentContext();

        // load helper functions.
        Helpers::load();

        // since facades share only on instance of container
        // set cotnainer instance to any one facade,
        // and every facade will be able to access container.
        Config::setContainer($this);

        // load environment specific global configuration
        $this->loadGlobalConfig();

        // load environment specific app configuration
        $this->scopeConfig();

        // verify base configurations
        $this->validateBaseConfig();

        // load service providers
        $this->loadProviders();

        return $this;
    }


    protected function environmentContext()
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
     * @return void
     */
    protected function loadGlobalConfig(): void
    {
        // Ensure configuration directory is set.
        if (!isset($this->configDir)) {
            throw new Exception("Configuration directory not specified.");
        }

        // Bind the Config instance as a singleton.
        $this->singleton(Configurator::class, function (): Configurator {
            return new Configurator($this->configDir, $this->environment);
        });

        // Retrieve and set the Config instance.
        $this->configurator = $this->make(Configurator::class);
    }


    // loads app scoped configuration.
    protected function scopeConfig()
    {
        $configScope = $this->resolve(AppConfigScope::class, ['environment' => $this->environment]);

        $configScope->appConfigByFiles($this->appConfigFiles);
        $configScope->scope($_SERVER['REQUEST_URI']);
    }

    /**
     * Load and register providers from both configuration and inline definitions.
     *
     * @return void
     */
    protected function loadProviders(): void
    {
        // Retrieve providers from the configuration.
        $providers = $this->configurator ? $this->configurator->get('providers', []) : [];

        // Register providers through the container.
        $this->registerProviders($providers);

        // Boot providers.
        $this->bootProviders();
    }


    public function isDebug(): bool
    {
        // Default is always false.
        $isDebug = false;

        // Override with configuration if set, otherwise use the default.
        $isDebug = $this->configurator->get('app.isDebug', $isDebug);

        // Environment override: true if not in production.
        return $this->environment !== 'production' ? true : $isDebug;
    }

    /**
     * Handle an incoming HTTP request.
     *
     * @return Response
     */
    public function handleHTTP(): Response
    {
        $baseURI = $this->configurator->get('app.base_uri');
        $this->request = $this->resolve(RequestFactory::class, ['baseURI' => $baseURI])::fromGlobals();

        if (!$baseURI) {
            throw new Exception("Configuration 'app.base_uri' cannot be empty");
        }

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
                'baseURI' => $baseURI
            ]
        );


        // Process the request through the Kernel and return the response.
        return $kernel->handle($this->request);
    }


    public function getConfig(string $key, mixed $default = null)
    {
        // delegates to configuration...
        return $this->configurator->get($key, $default);
    }


    protected function validateBaseConfig()
    {
    }
}
