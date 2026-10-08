<?php

namespace Arbor\bootstrap;

use Arbor\config\Configurator;
use Arbor\container\ServiceContainer;
use Arbor\container\ServiceProvider;
use Arbor\facade\Facade;
use Arbor\config\Config;
use Arbor\support\Helpers;

class App
{
    protected ServiceContainer $container;

    public function __construct()
    {
        Helpers::load();

        $this->container = new ServiceContainer();

        Facade::setContainer($this->container);
    }

    public function eagerLoad(ServiceProvider|string|array $providers): self
    {
        $this->providers($providers);
        $this->boot();

        return $this;
    }

    public function providers(ServiceProvider|string|array $providers): self
    {
        $providers = is_array($providers) ? $providers : [$providers];
        $this->container->registerProviders($providers);

        return $this;
    }

    public function boot(): self
    {
        $this->container->bootProviders();
        $this->container->finalizeProviders();

        return $this;
    }

    public function get(string $fqn): mixed
    {
        return $this->container->get($fqn);
    }

    public function container()
    {
        return $this->container;
    }
}
