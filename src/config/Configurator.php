<?php

namespace Arbor\config;

use Arbor\config\expressions\ExpressionInterface;
use Exception;

/**
 * Configurator class manages application configuration with support for environments,
 * expressions, and compile-time finalization.
 * 
 * This class provides a two-phase configuration system:
 * - Before finalization: configuration can be mutated (set, merge)
 * - After finalization: configuration is compiled and can only be accessed (get, all)
 */
class Configurator
{
    /**
     * The configuration registry that stores all configuration data.
     *
     * @var Registry
     */
    protected Registry $registry;

    /**
     * The compiler responsible for processing configuration expressions.
     *
     * @var Compiler
     */
    protected Compiler $compiler;

    /**
     * Whether the configuration has been finalized.
     * Once finalized, configuration cannot be mutated.
     *
     * @var bool
     */
    protected bool $finalized = false;

    /**
     * Create a new Configurator instance.
     *
     * @param string $path The path to the configuration directory
     * @param string|null $environment Optional environment name for loading environment-specific overrides
     */
    public function __construct(string $path = 'configs', ?string $environment = null)
    {
        $this->registry = new Registry();
        $this->compiler = new Compiler($this->registry);

        // Load base configuration
        $this->registry->loadConfig($path);

        // Load environment-specific configuration overrides if provided
        $this->registry->mergeEnvironment($path, $environment);
    }

    /**
     * Get a configuration value by key.
     * Can only be called after finalization.
     *
     * @param string $key The configuration key in dot notation (e.g., 'database.host')
     * @param mixed $default Optional default value if key doesn't exist
     * @return mixed The configuration value
     * @throws Exception If called before finalization
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->accessor();

        $hasDefault = func_num_args() === 2;

        if ($hasDefault) {
            return $this->registry->get($key, $default);
        }

        return $this->registry->get($key);
    }

    /**
     * Get all configuration values.
     * Can only be called after finalization.
     *
     * @return array<string, mixed> All configuration data
     * @throws Exception If called before finalization
     */
    public function all(): array
    {
        $this->accessor();

        return $this->registry->all();
    }

    /**
     * Set a configuration value.
     * Can only be called before finalization.
     *
     * @param string $key The configuration key in dot notation
     * @param mixed $value The value to set
     * @return void
     * @throws Exception If called after finalization
     */
    public function set(string $key, mixed $value): void
    {
        $this->mutator();

        $this->registry->set($key, $value);
    }

    /**
     * Merge configuration from a directory with environment overrides.
     * Can only be called before finalization.
     *
     * @param string $path The path to the configuration directory
     * @param string|null $environment Optional environment name for loading environment-specific overrides
     * @return void
     * @throws Exception If called after finalization
     */
    public function mergeByDir(string $path, ?string $environment = null): void
    {
        $this->mutator();

        $this->registry->loadConfig($path, true);
        $this->registry->mergeEnvironment($path, $environment);
    }

    /**
     * Finalize the configuration.
     * Compiles all expressions and locks the configuration from further mutations.
     * After calling this method, only accessor methods (get, all, touch) can be used.
     *
     * @return void
     * @throws Exception If called after already finalized
     */
    public function finalize(): void
    {
        $this->mutator();

        $this->registry->replace(
            $this->compiler->compile(
                $this->registry->getRaw()
            )
        );

        $this->finalized = true;
    }

    /**
     * Check if configuration has been finalized and throw if it has.
     * Used to guard mutation operations.
     *
     * @return void
     * @throws Exception If configuration has been finalized
     */
    protected function mutator(): void
    {
        if ($this->finalized) {
            throw new Exception("Cannot mutate Config after finalized");
        }
    }

    /**
     * Check if configuration has been finalized and throw if it hasn't.
     * Used to guard accessor operations.
     *
     * @return void
     * @throws Exception If configuration has not been finalized
     */
    protected function accessor(): void
    {
        if (!$this->finalized) {
            throw new Exception("Cannot access Config before finalized");
        }
    }

    /**
     * Touch a configuration value to retrieve it before finalization.
     * This method bypasses the finalization requirement but will throw
     * if the value is an unresolved expression.
     *
     * @param string $key The configuration key in dot notation
     * @param mixed $default Optional default value if key doesn't exist
     * @return mixed The configuration value
     * @throws Exception If the value is an expression (not yet compiled)
     */
    public function touch(string $key, mixed $default = null): mixed
    {
        $hasDefault = func_num_args() === 2;

        if ($hasDefault) {
            $value = $this->registry->get($key, $default);
        } else {
            $value = $this->registry->get($key);
        }

        if ($value instanceof ExpressionInterface) {
            throw new Exception("Cannot touch config '{$key}' is an expression, touchable keys should have static value.", 1);
        }

        return $value;
    }

    /**
     * Register a macro (custom function) that can be used in configuration expressions.
     *
     * @param string $name The name of the macro
     * @param callable $callable The callable to execute when the macro is invoked
     * @return void
     */
    public function macro(string $name, callable $callable): void {
        $this->registry->addMacro($name, $callable);
    }
}