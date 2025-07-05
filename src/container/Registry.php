<?php

namespace Arbor\container;


use Closure;
use Exception;
use Arbor\container\ServiceBond;

/**
 * Class Registry
 *
 * A simple dependency injection container registry that allows binding of services,
 * aliasing keys, and managing shared instances.
 * 
 * @package Arbor\container
 * 
 */
class Registry
{
    /**
     * Array of service bindings.
     *
     * @var ServiceBond[]
     */
    private array $bindings = [];

    /**
     * Array of alias names for bindings.
     *
     * @var array<string, string>
     */
    private array $aliases = [];

    /**
     * Array of shared service instances.
     *
     * @var array<string, mixed>
     */
    private array $sharedInstances = [];

    /**
     * Stores the last bound key to allow aliasing without explicit key.
     *
     * @var string|null
     */
    private ?string $lastBondKey = null;

    /**
     * Bind a service resolver to a fully qualified name.
     *
     * @param string                    $fqn      The fully qualified name of the service.
     * @param Closure|callable|string|array $resolver The resolver to instantiate the service.
     * @param bool                      $isShared Determines if the service should be shared (singleton).
     *
     * @return string Returns the fully qualified name used as the binding key.
     */
    public function bind(string $fqn, Closure|callable|string|array $resolver, bool $isShared = false): string
    {
        $this->bindings[$fqn] = new ServiceBond($fqn, $resolver, $isShared);
        $this->lastBondKey = $fqn;

        return $fqn;
    }

    /**
     * Add an alias for a binding.
     *
     * @param string      $aliasName The alias name to assign.
     * @param string|null $key       The original binding key; if null, uses the last bound key.
     *
     * @throws Exception If no valid binding is found for the provided key.
     *
     * @return void
     */
    public function addAliasName(string $aliasName, ?string $key = null): void
    {
        $bindingKey = $key ?? $this->lastBondKey;

        if (!$bindingKey) {
            throw new Exception("No valid binding found to add alias name: '{$aliasName}' on Key: '{$key}'");
        }

        $this->aliases[$aliasName] = $bindingKey;
    }

    /**
     * Get the resolved fully qualified name key.
     *
     * @param string $key The alias or original key.
     *
     * @return string The resolved fully qualified name key.
     */
    public function getFqnKey(string $key): string
    {
        return $this->aliases[$key] ?? $key;
    }

    /**
     * Check if a service is registered or shared.
     *
     * @param string $key The alias or binding key.
     *
     * @return bool True if the service is registered or has a shared instance, false otherwise.
     */
    public function has(string $key): bool
    {
        // Resolve alias if needed.
        $fqn = $this->getFqnKey($key);

        // Check if the service is bound or has a shared instance.
        return isset($this->bindings[$fqn]) || isset($this->sharedInstances[$fqn]);
    }

    /**
     * Retrieve the binding for a given key.
     *
     * @param string $key The alias or binding key.
     *
     * @throws Exception If no binding is found for the key.
     *
     * @return ServiceBond The corresponding service bond.
     */
    public function getBinding(string $key): ServiceBond
    {
        $fqn = $this->getFqnKey($key);

        if (!isset($this->bindings[$fqn])) {
            throw new Exception("No binding found for key: {$key}");
        }

        return $this->bindings[$fqn];
    }

    /**
     * Retrieve the shared instance for a given key, if it exists.
     *
     * @param string $key The alias or binding key.
     *
     * @return mixed|null The shared instance or null if not set.
     */
    public function getSharedInstance(string $key): mixed
    {
        $fqn = $this->getFqnKey($key);

        return $this->sharedInstances[$fqn] ?? null;
    }

    /**
     * Set a shared instance for a given binding key.
     *
     * @param string $key      The alias or binding key.
     * @param mixed  $instance The instance to be shared.
     *
     * @return void
     */
    public function setSharedInstance(string $key, mixed $instance): void
    {
        $fqn = $this->getFqnKey($key);
        $this->sharedInstances[$fqn] = $instance;
    }
}
