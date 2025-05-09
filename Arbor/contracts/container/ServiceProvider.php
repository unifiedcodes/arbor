<?php

namespace Arbor\contracts\container;

use Arbor\contracts\Container\ContainerInterface;

/**
 * Class ServiceProvider
 *
 * Providers extending this class must implement the register() method to bind services
 * to the container. Optionally, they can override the boot() method for additional initialization
 * and the provides() method for specifying deferred services.
 *
 * @package Arbor\Contracts
 */
abstract class ServiceProvider
{
    /**
     * Indicates if the provider is deferred.
     *
     * When true, the provider's services will be loaded only when needed.
     *
     * @var bool
     */
    protected bool $deferred = false;

    /**
     * Register services with the container.
     *
     * This method should bind any services or perform any registration logic needed.
     *
     * @param ContainerInterface $container The container instance.
     *
     * @return void
     */
    abstract public function register(ContainerInterface $container): void;

    /**
     * Boot services after all providers have been registered.
     *
     * Override this method to perform any initialization that must occur after all providers
     * have been registered. The default implementation is empty.
     *
     * @param ContainerInterface $container The container instance.
     *
     * @return void
     */
    public function boot(ContainerInterface $container): void
    {
        // Optional boot logic.
    }

    /**
     * Get the list of services provided by this provider.
     *
     * This method is used for deferred providers to specify which service keys should trigger the provider's loading.
     *
     * @return array An array of service keys.
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Check if the provider is deferred.
     *
     * @return bool True if the provider is deferred, false otherwise.
     */
    public function isDeferred(): bool
    {
        return $this->deferred;
    }



    public function aliases(): array
    {
        return [];
    }
}
