<?php

namespace Arbor\bootstrap;

use Arbor\config\Configurator;
use Exception;
use Arbor\attributes\ConfigValue;

/**
 * AppConfigScope class
 * 
 * Handles application configuration scoping based on request URIs.
 * Allows for multi-application setups with different configuration directories.
 * 
 */
class AppConfigScope
{
    /**
     * Application configurator instance
     */
    protected Configurator $configurator;

    /**
     * Current environment (e.g., 'production', 'development')
     */
    protected string $environment;

    /**
     * Base URI for the application
     */
    protected string $base_uri;

    /**
     * Root directory of the application
     */
    protected string $root_dir;

    /**
     * Application configuration array
     */
    protected array $appConfig;

    /**
     * Flag indicating if multiple applications are configured
     */
    protected bool $hasMultipleApps = false;

    /**
     * Constructor for AppConfigScope
     * 
     * @param Configurator $configurator   The configurator instance
     * @param string       $base_uri       Global base URI from config
     * @param string       $root_dir       Root directory from config
     * @param string       $environment    Current environment
     */
    public function __construct(
        Configurator $configurator,

        #[ConfigValue('app.global_base_uri')]
        string $base_uri,

        #[ConfigValue('app.root_dir')]
        string $root_dir,

        string $environment
    ) {
        $this->configurator = $configurator;
        $this->environment = $environment;

        $this->root_dir = $root_dir;
        $this->base_uri = $base_uri;
    }

    /**
     * Load application configurations from files
     * 
     * @param array<string> $files   Array of configuration file paths
     * @return void
     */
    public function appConfigByFiles(array $files): void
    {
        if (empty($files)) {
            return;
        }

        $appConfigs = [];

        foreach ($files as $file) {
            $file_path = rtrim(normalizeDirPath($this->root_dir . DIRECTORY_SEPARATOR . $file), DIRECTORY_SEPARATOR);
            $appConfigs[] = require_once($file_path);
        }

        $this->configurator->set('app.apps', $appConfigs);
    }

    /**
     * Determine and set application scope based on request URI
     * 
     * @param string $request_uri   The incoming request URI
     * @throws Exception            When no configured apps are found
     * @return void
     */
    public function scope(string $request_uri): void
    {
        $appConfig = $this->configurator->get('app.app', null)
            ?? $this->configurator->get('app.apps', null);

        if (!$appConfig) {
            throw new Exception("No configured apps found with either 'app.app' or 'app.apps' key");
        }

        $this->appConfig = $appConfig;

        $this->hasMultipleApps = array_is_list($this->appConfig);

        $requestedPath = $this->getRelativeUri($request_uri, $this->base_uri);

        $app_key = $this->getAppKey($requestedPath);

        $found_app = $this->findApp($app_key);

        $this->setAppScope($found_app);
        $this->mergeConfig($found_app['config_dir']);
    }

    /**
     * Merge configuration files from the application config directory
     * 
     * @param string $config_dir   Configuration directory path
     * @return void
     */
    protected function mergeConfig(string $config_dir): void
    {
        $this->configurator->mergeByDir($config_dir, $this->environment);
    }

    /**
     * Set the current application scope in the configurator
     * 
     * @param array<string, mixed> $app   Application configuration
     * @return void
     */
    protected function setAppScope(array $app): void
    {
        $this->configurator->set('app', $app);
    }

    /**
     * Find the application configuration based on URI prefix
     * 
     * @param string $app_key   Application key derived from URI
     * @throws Exception        When no matching app is found
     * @return array<string, mixed>   The matched application configuration
     */
    protected function findApp(string $app_key): array
    {
        if (!$this->hasMultipleApps) {
            return $this->appConfig;
        }

        $defaultApp = null;

        foreach ($this->appConfig as $app) {
            if ($app['uri_prefix'] === $app_key) {
                return $app; // return as soon as matched
            }

            if (
                $app['uri_prefix'] === ''
                || $app['uri_prefix'] === 'default'
            ) {
                $defaultApp = $app; // store fallback
            }
        }

        if ($defaultApp !== null) {
            return $defaultApp;
        }

        throw new Exception("No matching app found for prefix: '$app_key'");
    }

    /**
     * Extract the application key from the requested path
     * 
     * Gets the first segment of the path to use as application key
     * 
     * @param string $requestedPath   The relative URI path
     * @return string                 The extracted application key
     */
    protected function getAppKey(string $requestedPath): string
    {
        // Manually scan characters to find first segment
        // trade off to save performance cost of more readable ways.
        $length = strlen($requestedPath);
        $start = 0;

        while ($start < $length && $requestedPath[$start] === '/') {
            $start++;
        }

        if ($start === $length) return '';

        $end = strpos($requestedPath, '/', $start);
        return $end === false ? substr($requestedPath, $start) : substr($requestedPath, $start, $end - $start);
    }

    /**
     * Get the relative URI path by removing the base URI
     * 
     * @param string $requestURI   The full incoming request URI
     * @param string $base_uri     The configured base URI
     * @throws Exception           When the base URI configuration is empty
     * @return string              The relative URI path
     */
    protected function getRelativeUri(string $requestURI, string $base_uri): string
    {
        $base_uri = trim($base_uri);

        if ($base_uri === '') {
            throw new Exception("Configuration 'app.baseURI' cannot be empty!");
        }

        // parse baseuri
        $parseURI = parse_url($base_uri);

        if (!isset($parseURI['scheme'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $base_uri = $scheme . $base_uri;
            $parseURI = parse_url($base_uri);
        }

        $basePath = isset($parseURI['path']) ? $parseURI['path'] : '';
        $basePath = rtrim($basePath, '/');

        $requestURI = empty($requestURI) ? '/' : $requestURI;
        $parseRequestURI = parse_url($requestURI);

        $requestedPath = isset($parseRequestURI['path']) ? $parseRequestURI['path'] : '';
        $requestedPath = '/' . ltrim($requestedPath, '/');

        if ($basePath && str_starts_with($requestedPath, $basePath . '/')) {
            $relative = substr($requestedPath, strlen($basePath));
            return '/' . ltrim($relative, '/'); // always return path with leading slash
        }

        return $requestedPath;
    }
}
