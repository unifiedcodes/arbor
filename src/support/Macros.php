<?php

namespace Arbor\support;

/**
 * Trait Macros
 * 
 * Provides dynamic method registration and invocation capabilities for classes.
 * Allows adding custom methods (macros) at runtime for both instance and static contexts.
 */
trait Macros
{
    /**
     * Store instance-level macros
     * 
     * @var array<string, callable> Array of callable macros keyed by method name
     */
    protected array $instanceMacros = [];

    /**
     * Store class-level static macros
     * 
     * @var array<string, callable> Array of callable macros keyed by method name
     */
    protected static array $staticMacros = [];

    /**
     * Register a new macro
     * 
     * Adds a callable as a dynamically invokable method on the class.
     * Can be registered as either an instance method or a static method.
     * 
     * @param string $name The name of the macro method
     * @param callable $callable The callable to execute when the macro is invoked
     * @param bool $static Whether to register as a static macro (default: false)
     * @return void
     */
    public function addMacro(string $name, callable $callable, bool $static = false): void
    {
        if ($static) {
            static::$staticMacros[$name] = $callable;
        } else {
            $this->instanceMacros[$name] = $callable;
        }
    }

    /**
     * Check if a macro exists
     * 
     * Determines whether a macro with the given name has been registered.
     * 
     * @param string $name The name of the macro to check
     * @param bool $static Whether to check static macros (default: false)
     * @return bool True if the macro exists, false otherwise
     */
    public function hasMacro(string $name, bool $static = false): bool
    {
        return $static
            ? isset(static::$staticMacros[$name])
            : isset($this->instanceMacros[$name]);
    }

    /**
     * Retrieve a registered macro
     * 
     * Returns the callable associated with the given macro name, or null if not found.
     * 
     * @param string $name The name of the macro to retrieve
     * @param bool $static Whether to retrieve from static macros (default: false)
     * @return callable|null The macro callable, or null if not found
     */
    public function getMacro(string $name, bool $static = false): ?callable
    {
        return $static
            ? (static::$staticMacros[$name] ?? null)
            : ($this->instanceMacros[$name] ?? null);
    }

    /**
     * Handle dynamic instance method calls
     * 
     * Intercepts calls to undefined instance methods and attempts to invoke
     * a registered instance macro. If the macro is a Closure, it will be
     * bound to the current instance for access to $this.
     * 
     * @param string $name The name of the method being called
     * @param array $arguments The arguments passed to the method
     * @return mixed The result of the macro execution
     * @throws \BadMethodCallException If the macro does not exist
     */
    public function __call(string $name, array $arguments)
    {
        if (!$this->hasMacro($name)) {
            throw new \BadMethodCallException("Instance macro '{$name}' does not exist.");
        }

        $callable = $this->getMacro($name);

        // Bind closures to the instance
        if ($callable instanceof \Closure) {
            $callable = $callable->bindTo($this, $this);
        }

        return $callable(...$arguments);
    }

    /**
     * Handle dynamic static method calls
     * 
     * Intercepts calls to undefined static methods and attempts to invoke
     * a registered static macro. Note that static macros cannot be bound
     * to an instance context.
     * 
     * @param string $name The name of the static method being called
     * @param array $arguments The arguments passed to the method
     * @return mixed The result of the macro execution
     * @throws \BadMethodCallException If the static macro does not exist
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if (!isset(static::$staticMacros[$name])) {
            throw new \BadMethodCallException("Static macro '{$name}' does not exist.");
        }

        $callable = static::$staticMacros[$name];

        // Note: Static context binding is not the same as instance binding
        return $callable(...$arguments);
    }
}
