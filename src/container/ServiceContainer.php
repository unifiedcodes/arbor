<?php

namespace Arbor\container;

use Arbor\container\ContainerInterface;

use Arbor\container\Registry;
use Arbor\container\Resolver;
use Arbor\container\Providers;

/**
 * Class Container
 *
 * Acts as a facade for the dependency injection system,
 * providing a seamless interface for binding, resolving,
 * aliasing, and calling services.
 *
 * @package Arbor\container
 */
class ServiceContainer implements ContainerInterface
{
    protected Registry $registry;
    protected Resolver $resolver;
    protected Providers $providers;

    /**
     * Container constructor.
     *
     * Initializes the underlying Registry and Resolver instances.
     * utilize late binding to bind resolver and parameters with bootstrapped class.
     */
    public function __construct()
    {
        $this->registry = new Registry();
        $this->resolver = new Resolver($this->registry);
        $this->providers = new Providers($this->registry, $this->resolver);
    }

    /**
     * Bind a service with the provided resolver.
     *
     * @param string $fqn The fully qualified name.
     * @param mixed  $resolver The resolver callable/class name.
     *
     * @return void
     */
    public function bind(string $fqn, mixed $resolver): void
    {
        $this->registry->bind($fqn, $resolver);
    }

    /**
     * Bind a service as a singleton.
     *
     * @param string $fqn The fully qualified name.
     * @param mixed  $resolver The resolver callable/class name.
     *
     * @return void
     */
    public function singleton(string $fqn, mixed $resolver): void
    {
        $this->registry->bind($fqn, $resolver, true);
    }

    /**
     * Register an alias for a binding.
     *
     * @param string      $aliasName The alias name.
     * @param string|null $key       The original binding key.
     *
     * @return void
     */
    public function alias(string $aliasName, ?string $key = null): void
    {
        $this->registry->addAliasName($aliasName, $key);
    }

    /**
     * Check if a service is registered.
     *
     * @param string $key The alias or binding key.
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->registry->has($key);
    }

    /**
     * resolve if a service is registered.
     *
     * @param string $key The alias or binding key.
     *
     * @return mixed
     */
    public function get(string $key): mixed
    {
        return $this->resolve($key);
    }

    /**
     * Resolve an instance by its key.
     *
     * @param string $key          The binding key or alias.
     * @param array  $customParams Custom parameters for dependency resolution.
     *
     * @return mixed
     */
    public function resolve(string $key, array $customParams = []): mixed
    {
        // Check and load deferred provider if needed.
        $this->providers->loadDeferred($key);

        return $this->resolver->get($key, $customParams);
    }

    /**
     * Alias for resolve(), providing a more expressive API.
     *
     * @param string $key          The binding key or alias.
     * @param array  $customParams Custom parameters for dependency resolution.
     *
     * @return mixed
     */
    public function make(string $key, array $customParams = []): mixed
    {
        return $this->resolve($key, $customParams);
    }

    /**
     * Register an already instantiated object as a shared instance.
     *
     * @param string $key      The binding key or alias.
     * @param mixed  $instance The pre-instantiated object.
     *
     * @return void
     */
    public function setInstance(string $key, mixed $instance): void
    {
        $this->registry->setSharedInstance($key, $instance);
    }


    public function getInstance(string $key): mixed
    {
        return $this->registry->getSharedInstance($key);
    }

    /**
     * Call a callable in a non-static context using the resolver.
     *
     * @param mixed $callable     The callable or method to invoke.
     * @param array $customParams Custom parameters for dependency resolution.
     *
     * @return mixed
     */
    public function call(mixed $callable, array $customParams = []): mixed
    {
        return $this->resolver->call($callable, $customParams, false);
    }

    /**
     * Call a static method or callable using the resolver.
     *
     * @param mixed $callable     The static callable to invoke.
     * @param array $customParams Custom parameters for dependency resolution.
     *
     * @return mixed
     */
    public function callStatic(mixed $callable, array $customParams = []): mixed
    {
        return $this->resolver->call($callable, $customParams, true);
    }

    /**
     * Register multiple service providers.
     *
     * @param array $providers Array of provider classes.
     *
     * @return void
     */
    public function registerProviders(array $providers): void
    {
        $this->providers->registerProviders($providers);
    }

    /**
     * Boot all registered service providers.
     *
     * @return void
     */
    public function bootProviders(): void
    {
        $this->providers->bootProviders();
    }

    /**
     * Retrieve the underlying Registry instance.
     *
     * @return Registry
     */
    public function getRegistry(): Registry
    {
        return $this->registry;
    }
}
