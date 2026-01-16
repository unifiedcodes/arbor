<?php

namespace Arbor\support;

trait Macros
{
    /**
     * @var array<string, callable>
     */
    protected array $instanceMacros = [];

    /**
     * @var array<string, callable>
     */
    protected static array $staticMacros = [];

    public function addMacro(string $name, callable $callable, bool $static = false): void
    {
        if ($static) {
            static::$staticMacros[$name] = $callable;
        } else {
            $this->instanceMacros[$name] = $callable;
        }
    }

    public function hasMacro(string $name, bool $static = false): bool
    {
        return $static
            ? isset(static::$staticMacros[$name])
            : isset($this->instanceMacros[$name]);
    }

    public function getMacro(string $name, bool $static = false): ?callable
    {
        return $static
            ? (static::$staticMacros[$name] ?? null)
            : ($this->instanceMacros[$name] ?? null);
    }

    /**
     * Handle dynamic instance calls to macros
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
     * Handle dynamic static calls to macros
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
