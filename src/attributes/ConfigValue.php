<?php

namespace Arbor\attributes;


use Attribute;
use Arbor\contracts\metadata\AttributeInterface;
use Arbor\config\Configurator;
use Exception;

/**
 * Class ConfigValue
 *
 * An attribute used to inject configuration values.
 * When applied, it resolves the configuration value from a given key.
 *
 * @package Arbor\attributes
 */
#[Attribute]
class ConfigValue implements AttributeInterface
{
    /**
     * The configuration key to retrieve.
     *
     * @var string
     */
    protected string $key;
    protected mixed $default;

    /**
     * The Config instance used to resolve the value.
     *
     * @var Configurator|null
     */
    protected ?Configurator $config = null;

    /**
     * ConfigValue constructor.
     *
     * @param string $key The configuration key.
     */
    public function __construct(string $key, mixed $default = null)
    {
        $this->key = $key;
        $this->default = $default;
    }

    /**
     * Sets the Config instance required for resolving the value.
     *
     * @param Configurator $config The configuration instance.
     *
     * @return void
     */
    public function require(Configurator $config): void
    {
        $this->config = $config;
    }

    /**
     * Resolves and returns the configuration value for the given key.
     *
     * @return mixed The configuration value.
     *
     * @throws Exception If the Config instance has not been provided.
     */
    public function resolve(): mixed
    {
        if ($this->config === null) {
            throw new Exception("Config instance not found.");
        }

        return $this->config->get($this->key, $this->default);
    }
}
