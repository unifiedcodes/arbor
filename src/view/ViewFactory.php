<?php

namespace Arbor\view;

use Closure;
use Exception;
use Arbor\view\Builder;
use Arbor\fragment\Fragment;
use Arbor\attributes\ConfigValue;

/**
 * ViewFactory - Elegant View Builder Management
 * 
 * A sophisticated factory for creating and managing view Builder instances with
 * predefined configurations. This factory provides a clean, fluent interface
 * for defining view presets and creating consistently configured builders.
 * 
 * Features:
 * • Preset management with named configurations
 * • Default preset support for streamlined workflows  
 * • Type-safe configurator closures
 * • Shared configuration across all views
 * • Dependency injection integration
 * 
 * @package Arbor\view
 */
final class ViewFactory
{
    // ========================================================================
    // Properties
    // ========================================================================

    /**
     * Collection of registered preset configurations
     * 
     * Each preset is defined as a closure that receives a Builder instance
     * and shared configuration array for customization.
     * 
     * @var array<string, [Closure(Builder, array): void]>
     */
    protected array $presets = [];

    /**
     * Cache for resolved Builder instances
     * 
     * Currently unused but reserved for future optimization where
     * Builder instances might be cached and reused.
     * 
     * @var array<string, Builder>
     */
    protected array $resolved = [];

    /**
     * The root directory path containing view template files
     * 
     * @var string
     */
    protected string $view_dir;

    /**
     * Fragment renderer for processing view templates
     * 
     * @var Fragment
     */
    protected Fragment $fragment;

    /**
     * The identifier of the currently active default preset
     * 
     * When null, no default preset has been configured and
     * explicit preset names must be provided.
     * 
     * @var string|null
     */
    protected string|null $defaultPreset = null;

    /**
     * Reserved for future preset inheritance functionality
     * 
     * @var string|null
     */
    protected ?string $extendingPreset = null;

    /**
     * Global configuration shared across all view instances
     * 
     * This configuration is passed to every preset configurator
     * and can contain application-wide view settings.
     * 
     * @var array<string, mixed>
     */
    protected array $config = [];


    protected array $sharedData = [];

    // ========================================================================
    // Constructor & Initialization
    // ========================================================================

    /**
     * Initialize the ViewFactory with core dependencies
     * 
     * Sets up the factory with the necessary components for creating
     * and configuring view Builder instances.
     * 
     * @param string $view_dir The directory path where view files are located
     * @param Fragment $fragment Fragment instance for view rendering
     */
    public function __construct(
        #[ConfigValue('app.views_dir')]
        string $view_dir,
        Fragment $fragment,
    ) {
        $this->view_dir = $view_dir;
        $this->fragment = $fragment;
    }

    // ========================================================================
    // Configuration Management
    // ========================================================================

    /**
     * Set global configuration shared by all views
     * 
     * This configuration will be passed to every preset configurator,
     * allowing for application-wide view customization.
     * 
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }


    public function setSharedData(array $data): void
    {
        $this->sharedData = $data;
    }

    // ========================================================================
    // Preset Management
    // ========================================================================

    /**
     * Register a new preset configuration
     * 
     * Presets allow you to define reusable Builder configurations that can
     * be applied consistently across your application. The configurator closure
     * receives a fresh Builder instance and the shared configuration array.
     * 
     * Example:
     * ```php
     * $factory->setPreset('api', function(Builder $builder, array $config) {
     *     $builder->setContentType('application/json')
     *             ->addMiddleware($config['api_middleware']);
     * });
     * ```
     * 
     * @param string $name The unique identifier for this preset
     * @param Closure(Builder, array): void $configurator Closure that configures a Builder instance
     * @return void
     */
    public function setPreset(string $name, Closure $configurator): void
    {
        if (!isset($this->presets[$name])) {
            $this->presets[$name] = [];
        }
        $this->presets[$name][] = $configurator;
    }

    /**
     * Configure the default preset for convenient access
     * 
     * The default preset is used when no specific preset name is provided
     * to getPreset(). This can be either an existing preset name or a
     * new configurator closure.
     * 
     * @param string|Closure(Builder, array): void $preset Either a preset name or configurator closure
     * @return void
     * @throws Exception When the specified preset name doesn't exist
     */
    public function setDefaultPreset(string|Closure $preset): void
    {
        if ($preset instanceof Closure) {

            $this->setPreset('default', $preset);

            $this->defaultPreset = 'default';
            return;
        }

        // Validate that the named preset exists
        if (!isset($this->presets[$preset])) {
            throw new Exception("Preset with name '{$preset}' not found");
        }

        $this->defaultPreset = $preset;
    }


    /**
     * Extend an existing preset by adding another configurator.
     *
     * @param string $parentName Existing preset name to extend
     * @param string $newName New preset name to register
     * @param Closure(Builder, array): void $extraConfigurator
     * @throws Exception
     */
    public function extendPreset(string $parentName, string $newName, Closure $extraConfigurator): void
    {
        if (!isset($this->presets[$parentName])) {
            throw new Exception("Cannot extend non-existing preset '{$parentName}'");
        }

        // new preset = copy parent’s configurators + extraConfigurator
        $this->presets[$newName] = array_merge(
            $this->presets[$parentName],
            [$extraConfigurator]
        );
    }

    // ========================================================================
    // Builder Creation
    // ========================================================================

    /**
     * Create a Builder instance using a named preset or default
     * 
     * This is the primary method for obtaining configured Builder instances.
     * When no name is provided, the default preset will be used if available.
     * 
     * @param string|null $name The preset name to use, or null for default
     * @return Builder A fully configured Builder instance
     * @throws Exception When the specified preset is not found
     */
    public function getPreset(?string $name = null): Builder
    {
        if ($name === null) {
            return $this->getDefaultPreset();
        }

        if (!isset($this->presets[$name])) {
            throw new Exception("Preset with name '{$name}' not found");
        }

        return $this->resolveConfiguratorStack($this->presets[$name]);
    }

    /**
     * Create a Builder instance using the default preset
     * 
     * Provides direct access to the default preset configuration without
     * needing to call getPreset() with null parameter.
     * 
     * @return Builder A configured Builder instance using the default preset
     * @throws Exception When no default preset has been defined
     */
    public function getDefaultPreset(): Builder
    {
        if (!$this->defaultPreset) {
            throw new Exception("No default preset defined. Use setDefaultPreset() first.");
        }

        return $this->resolveConfiguratorStack($this->presets[$this->defaultPreset]);
    }

    // ========================================================================
    // Internal Implementation
    // ========================================================================

    /**
     * Execute a configurator closure to create a configured Builder
     * 
     * This method handles the instantiation of a new Builder and applies
     * the provided configuration closure to customize it with both the
     * Builder instance and shared configuration.
     * 
     * @param array  of configurator closures
     * @return Builder A freshly configured Builder instance
     */
    protected function resolveConfiguratorStack(array $configurators): Builder
    {
        $builder = new Builder($this->view_dir, $this->fragment);

        $builder->set('shared', $this->sharedData);

        foreach ($configurators as $configurator) {
            $configurator($builder, $this->config);
        }

        return $builder;
    }


    // ========================================================================
    // Delegator Methods
    // ========================================================================

    /**
     * Shorthand for setPreset() - Register a new preset configuration
     * 
     * @param string $name The unique identifier for this preset
     * @param Closure(Builder, array): void $configurator Closure that configures a Builder instance
     * @return void
     */
    public function set(string $name, Closure $configurator): void
    {
        $this->setPreset($name, $configurator);
    }

    /**
     * Shorthand for setDefaultPreset() - Configure the default preset
     * 
     * @param string|Closure(Builder, array): void $preset Either a preset name or configurator closure
     * @return void
     * @throws Exception When the specified preset name doesn't exist
     */
    public function default(string|Closure $preset): void
    {
        $this->setDefaultPreset($preset);
    }

    /**
     * Shorthand for getPreset() - Create a Builder instance using a named preset or default
     * 
     * @param string|null $name The preset name to use, or null for default
     * @return Builder A fully configured Builder instance
     * @throws Exception When the specified preset is not found
     */
    public function get(?string $name = null): Builder
    {
        return $this->getPreset($name);
    }


    /**
     * Extend an existing preset by adding another configurator.
     *
     * @param string $parentName Existing preset name to extend
     * @param string $newName New preset name to register
     * @param Closure(Builder, array): void $extraConfigurator
     * @throws Exception
     */
    public function extends(string $parentName, string $newName, Closure $extraConfigurator): void
    {
        $this->extendPreset($parentName, $newName, $extraConfigurator);
    }
}
