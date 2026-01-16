<?php

namespace Arbor\Config;

use Arbor\Config\Registry;
use Closure;
use LogicException;

/**
 * Provides context for resolving configuration values with access to registry and macros.
 * 
 * This class acts as a resolution context that maintains a reference to a registry
 * and an optional resolver callable. It provides methods to access static macros,
 * instance macros, global functions, and registry references.
 */
final class ResolverContext
{
    /**
     * Creates a new resolver context.
     * 
     * @param Registry $registry The configuration registry instance
     * @param Closure|null $resolver Optional resolver callable for processing values
     */
    public function __construct(
        private readonly Registry $registry,
        private ?Closure $resolver = null
    ) {}

    /**
     * Sets the resolver callable for this context.
     * 
     * @param callable $resolver The resolver callable to set
     * @return void
     * @throws LogicException If a resolver has already been set
     */
    public function setResolver(callable $resolver): void
    {
        // optional: guard to prevent replacing resolver more than once
        if ($this->resolver !== null) {
            throw new LogicException("Resolver already set");
        }
        $this->resolver = $resolver;
    }

    /**
     * Resolves a value using the configured resolver.
     * 
     * @param mixed $value The value to resolve
     * @return mixed The resolved value
     */
    public function resolve(mixed $value): mixed
    {
        return ($this->resolver)($value);
    }

    /**
     * Gets a reference from the registry by key.
     * 
     * @param string $key The registry key to lookup
     * @return mixed The value associated with the key
     */
    public function ref(string $key): mixed
    {
        return $this->registry->get($key);
    }

    /**
     * Checks if a static macro exists in the registry.
     * 
     * @param string $name The macro name to check
     * @return bool True if the static macro exists, false otherwise
     */
    public function hasStatic(string $name): bool
    {
        return $this->registry->hasMacro($name, static: true);
    }

    /**
     * Gets a static macro from the registry.
     * 
     * @param string $name The macro name to retrieve
     * @return callable|null The static macro callable or null if not found
     */
    public function getStatic(string $name): ?callable
    {
        return $this->registry->getMacro($name, static: true);
    }

    /**
     * Checks if an instance macro exists in the registry.
     * 
     * @param string $name The macro name to check
     * @return bool True if the instance macro exists, false otherwise
     */
    public function hasInstance(string $name): bool
    {
        return $this->registry->hasMacro($name, static: false);
    }

    /**
     * Gets an instance macro from the registry.
     * 
     * @param string $name The macro name to retrieve
     * @return callable|null The instance macro callable or null if not found
     */
    public function getInstance(string $name): ?callable
    {
        return $this->registry->getMacro($name, static: false);
    }

    /**
     * Checks if a global function exists.
     * 
     * @param string $name The function name to check
     * @return bool True if the global function exists, false otherwise
     */
    public function hasGlobal(string $name): bool
    {
        return function_exists($name);
    }

    /**
     * Gets a global function name if it exists.
     * 
     * @param string $name The function name to retrieve
     * @return string|null The function name if it exists, null otherwise
     */
    public function getGlobal(string $name): ?string
    {
        return $this->hasGlobal($name) ? $name : null;
    }
}