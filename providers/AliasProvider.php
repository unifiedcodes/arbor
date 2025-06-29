<?php

namespace providers;

use Arbor\contracts\container\ServiceProvider;
use Arbor\container\Container;
use Arbor\contracts\Container\ContainerInterface;
use Arbor\config\Configurator;
use Arbor\router\Router;

/**
 * Class AliasProvider
 *
 * This service provider is responsible for defining aliases for commonly used
 * services within the application. These aliases provide convenient shortcuts
 * to access core services from the dependency injection container.
 *
 * Unlike other providers, this provider doesn't register any new services;
 * it only creates aliases for services registered by other providers.
 *
 * @package Arbor\providers
 * 
 */
class AliasProvider extends ServiceProvider
{
    /**
     * Register method implementation (required by ServiceProvider contract).
     *
     * This provider doesn't register any services, so this method is empty.
     * All functionality is handled through the aliases() method instead.
     *
     * @param Container $container The dependency injection container instance.
     *
     * @return void
     */
    public function register(ContainerInterface $container): void
    {
        // This provider doesn't register any services.
    }

    /**
     * Define aliases for known core services.
     *
     * This method creates shorthand aliases that can be used to resolve
     * commonly used services from the container without using their
     * fully qualified class names.
     *
     * @return array<string, string> An associative array mapping alias names
     *                              to their corresponding service class names.
     */
    public function aliases(): array
    {
        return [
            'config' => Configurator::class,
            'router' => Router::class,
        ];
    }
}
