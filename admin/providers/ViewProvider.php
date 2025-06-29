<?php

namespace admin\providers;

use Arbor\contracts\container\ServiceProvider;
use Arbor\container\Container;
use Arbor\contracts\Container\ContainerInterface;
use Arbor\view\View;
use Arbor\bootstrap\App;
use Arbor\fragment\Fragment;
use admin\components\Sidebar;
use admin\components\Topbar;

/**
 * Class ServiceProvider
 *
 * This service provider is responsible for registering and configuring a View service
 * within the application. It handles the creation of service instances, configuration setup,
 * and any other initialization tasks required for the service to function properly.
 *
 * Note: Service providers can be deferred or non-deferred depending on when the service
 * needs to be available in the application lifecycle.
 *
 * @package Arbor\providers
 * 
 */
class ViewProvider extends ServiceProvider
{
    /**
     * Register the service as a singleton in the container.
     *
     * This method creates a new service instance with the required dependencies
     * and registers it in the dependency injection container.
     *
     * @param Container $container The dependency injection container instance.
     *
     * @return void
     */
    public function register(ContainerInterface $container): void {}

    /**
     * Boot the service by performing any post-registration configuration.
     * 
     * This method is called after all services are registered.
     * It handles any initialization that depends on other services being available,
     * such as loading configurations, establishing connections, or setting up resources.
     *
     * @param App $container The dependency injection container instance.
     * 
     * @return void
     */
    public function boot(ContainerInterface $container): void {}

    /**
     * Get the list of services provided by this provider.
     *
     * This method returns an array of service identifiers that
     * this provider is responsible for registering.
     *
     * @return string[] An array of provided service class names.
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Get the aliases for the services provided by this provider.
     *
     * This method defines shorthand aliases that can be used to
     * resolve the registered services from the container.
     *
     * @return array<string, string> An associative array of aliases mapped to their service classes.
     */
    public function aliases(): array
    {
        return [];
    }
}
