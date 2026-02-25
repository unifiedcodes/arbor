<?php

namespace Arbor\support;

use BadMethodCallException;

trait Macros
{
    /**
     * Instance-level macros (per object)
     *
     * @var array<string, callable>
     */
    protected array $instanceMacros = [];

    /**
     * Static macro storage keyed by class name
     *
     * @var array<string, array<string, callable>>
     */
    protected static array $staticMacros = [];

    public function addMacro(string $name, callable $callable, bool $static = false): void
    {
        if ($static) {
            $class = static::class;

            if (!isset(self::$staticMacros[$class])) {
                self::$staticMacros[$class] = [];
            }

            self::$staticMacros[$class][$name] = $callable;
        } else {
            $this->instanceMacros[$name] = $callable;
        }
    }

    public function hasMacro(string $name, bool $static = false): bool
    {
        if ($static) {
            $class = static::class;
            return isset(self::$staticMacros[$class][$name]);
        }

        return isset($this->instanceMacros[$name]);
    }

    public function getMacro(string $name, bool $static = false): ?callable
    {
        if ($static) {
            $class = static::class;
            return self::$staticMacros[$class][$name] ?? null;
        }

        return $this->instanceMacros[$name] ?? null;
    }

    public function __call(string $name, array $arguments)
    {
        if (!$this->hasMacro($name)) {
            throw new BadMethodCallException("Instance macro '{$name}' does not exist.");
        }

        $callable = $this->getMacro($name);

        if ($callable instanceof \Closure) {
            $callable = $callable->bindTo($this, $this);
        }

        return $callable(...$arguments);
    }

    public static function __callStatic(string $name, array $arguments)
    {
        $class = static::class;

        if (!isset(self::$staticMacros[$class][$name])) {
            throw new BadMethodCallException("Static macro '{$name}' does not exist.");
        }

        $callable = self::$staticMacros[$class][$name];

        return $callable(...$arguments);
    }
}
