<?php

namespace Arbor\container;

use InvalidArgumentException;

class Providers
{
    /**
     * Registered providers indexed by their FQN.
     *
     * @var array<string, ServiceProvider>
     */
    protected array $providers = [];

    /**
     * Deferred providers indexed by the services they provide.
     *
     * @var array<string, ServiceProvider>
     */
    protected array $deferred = [];

    /**
     * Providers that have already been booted.
     *
     * @var array<string, true>
     */
    protected array $booted = [];

    public function __construct(
        protected Registry $registry,
        protected Resolver $resolver
    ) {}

    public function registerProvider(ServiceProvider|string $provider): void
    {
        $provider = $this->resolveProvider($provider);

        $fqn = get_class($provider);

        // Provider has already been registered.
        if (isset($this->providers[$fqn])) {
            return;
        }

        // Register aliases once.
        $this->registerAliases($provider);

        if ($provider->isDeferred()) {
            $this->registerDeferred($provider);

            return;
        }

        // Register provider exactly once.
        $provider->register();

        $this->providers[$fqn] = $provider;
    }

    protected function resolveProvider(
        ServiceProvider|string $provider
    ): ServiceProvider {
        if ($provider instanceof ServiceProvider) {
            return $provider;
        }

        $providerInstance = $this->resolver->get($provider);

        if (!$providerInstance instanceof ServiceProvider) {
            throw new InvalidArgumentException(
                "Provider class {$provider} must extend ServiceProvider"
            );
        }

        return $providerInstance;
    }

    protected function registerAliases(ServiceProvider $provider): void
    {
        foreach ($provider->aliases() as $alias => $bindingKey) {
            $this->registry->addAliasName($alias, $bindingKey);
        }
    }

    protected function registerDeferred(ServiceProvider $provider): void
    {
        foreach ($provider->provides() as $serviceKey) {
            $this->deferred[$serviceKey] = $provider;
        }
    }

    public function registerProviders(array $providers): void
    {
        foreach ($providers as $provider) {
            $this->registerProvider($provider);
        }
    }

    public function bootProviders(): void
    {
        foreach ($this->providers as $fqn => $provider) {
            $this->bootProvider($fqn, $provider);
        }
    }

    protected function bootProvider(
        string $fqn,
        ServiceProvider $provider
    ): void {
        if (isset($this->booted[$fqn])) {
            return;
        }

        $provider->boot();

        $this->booted[$fqn] = true;
    }

    public function loadDeferred(string $serviceKey): void
    {
        if (!isset($this->deferred[$serviceKey])) {
            return;
        }

        $provider = $this->deferred[$serviceKey];
        $fqn = get_class($provider);

        // Remove all deferred entries belonging to this provider.
        foreach ($provider->provides() as $providedKey) {
            unset($this->deferred[$providedKey]);
        }

        // It may have already been registered through another path.
        if (!isset($this->providers[$fqn])) {
            $provider->register();
            $this->providers[$fqn] = $provider;
        }

        // Deferred providers are booted when first loaded.
        $this->bootProvider($fqn, $provider);
    }
}
