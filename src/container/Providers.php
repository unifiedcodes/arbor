<?php

namespace Arbor\container;

use Exception;
use Arbor\container\ServiceContainer;
use Arbor\contracts\container\ServiceProvider;

/**
 * Class Providers
 *
 * Manages the registration and booting of service providers,
 * including support for deferred providers.
 *
 * @package Arbor\container
 */
class Providers
{
    /**
     * The container instance.
     *
     * @var ServiceContainer
     */
    protected ServiceContainer $container;

    /**
     * Array of registered (immediate) service providers.
     *
     * @var ServiceProvider[]
     */
    protected array $providers = [];

    /**
     * Mapping of deferred service keys to their corresponding provider.
     *
     * @var array<string, ServiceProvider>
     */
    protected array $deferred = [];

    /**
     * Providers constructor.
     *
     * @param ServiceContainer $container The container instance.
     */
    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }

    /**
     * Register a single provider.
     *
     * Providers can be registered either as an instance or a class name.
     * If the provider is deferred, its services will be loaded only when needed.
     *
     * @param ServiceProvider|string $provider The provider instance or class name.
     *
     * @throws Exception if the resolved provider does not extend ServiceProvider.
     *
     * @return void
     */
    public function registerProvider(ServiceProvider|string $provider): void
    {
        // Resolve provider instance if a class name is given.
        if (is_string($provider)) {
            $providerInstance = $this->container->resolve($provider);
            if (!$providerInstance instanceof ServiceProvider) {
                throw new \InvalidArgumentException("Provider class {$provider} must extend ServiceProvider");
            }
        } else {
            $providerInstance = $provider;
        }

        // Always register aliases from the provider.
        $this->registerAliases($providerInstance);

        // If provider is deferred, store its provided service keys.
        if ($providerInstance->isDeferred()) {
            foreach ($providerInstance->provides() as $serviceKey) {
                $this->deferred[$serviceKey] = $providerInstance;
            }
        } else {
            // Register and boot immediately.
            $providerInstance->register($this->container);
            $this->providers[] = $providerInstance;
        }
    }



    protected function registerAliases(ServiceProvider $provider): void
    {
        foreach ($provider->aliases() as $alias => $bindingKey) {
            $this->container->alias($alias, $bindingKey);
        }
    }

    /**
     * Register multiple providers.
     *
     * @param array $providers Array of provider instances or class names.
     *
     * @return void
     */
    public function registerProviders(array $providers): void
    {
        foreach ($providers as $provider) {
            $this->registerProvider($provider);
        }
    }

    /**
     * Boot all registered (immediate) providers.
     *
     * This method should be called after all providers are registered.
     *
     * @return void
     */
    public function bootProviders(): void
    {
        foreach ($this->providers as $provider) {
            $provider->boot($this->container);
        }
    }

    /**
     * Load a deferred provider if the service key is deferred.
     *
     * This method checks if the requested service is provided by a deferred provider.
     * If found, it registers and boots the provider immediately.
     *
     * @param string $serviceKey The service key being resolved.
     *
     * @return void
     * 
     */
    public function loadDeferred(string $serviceKey): void
    {
        if (isset($this->deferred[$serviceKey])) {
            $provider = $this->deferred[$serviceKey];
            $provider->register($this->container);
            $provider->boot($this->container);
            $this->providers[] = $provider;

            // Remove all provided keys from the deferred mapping.
            foreach ($provider->provides() as $providedKey) {
                unset($this->deferred[$providedKey]);
            }
        }
    }
}
