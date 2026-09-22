<?php

namespace Arbor\bootstrap;

use Arbor\config\Configurator;
use Arbor\container\ServiceContainer;
use Arbor\container\ServiceProvider;
use Arbor\facades\Facade;
use Arbor\facades\Config;
use Arbor\support\Helpers;

class App
{
    protected $booted = false;
    protected ServiceContainer $container;

    public function __construct()
    {
        Helpers::load();

        $this->container = new ServiceContainer();

        Facade::setContainer($this->container);
    }

    public function boot(ServiceProvider|string $configProvider): self
    {
        if ($this->booted) {
            return $this;
        }

        // load configurator
        $this->container->registerProvider($configProvider);
        $this->container->bootProviders();

        // load providers
        $this->loadProviders('systemProviders');
        $this->loadProviders('moduleProviders');

        // mark booted and return self
        $this->booted = true;
        return $this;
    }

    protected function loadProviders(string $key = ""): void
    {
        $this->container->registerProviders(Config::touch($key));
        $this->container->bootProviders();
    }


    public function get(string $fqn): mixed
    {
        return $this->container->get($fqn);
    }
}
