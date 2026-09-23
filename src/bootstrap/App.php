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

    public function load(ServiceProvider|string|array $providers): self
    {
        $providers = is_array($providers) ? $providers : [$providers];

        $this->container->registerProviders($providers);
        $this->container->bootProviders();

        return $this;
    }

    public function get(string $fqn): mixed
    {
        return $this->container->get($fqn);
    }
}
