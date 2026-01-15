<?php

namespace Arbor\config;

use Arbor\config\expressions\ExpressionInterface;
use Exception;


class Configurator
{
    protected Registry $registry;
    protected Compiler $compiler;
    protected bool $finalized = false;


    public function __construct(string $path = 'configs', ?string $environment = null)
    {
        $this->registry = new Registry();
        $this->compiler = new Compiler($this->registry);

        // Load base configuration
        $this->registry->loadConfig($path);

        // Load environment-specific configuration overrides if provided
        $this->registry->mergeEnvironment($path, $environment);
    }


    public function get(string $key, mixed $default = null): mixed
    {
        $this->accessor();

        $hasDefault = func_num_args() === 2;

        if ($hasDefault) {
            return $this->registry->get($key, $default);
        }

        return $this->registry->get($key);
    }


    public function all(): array
    {
        $this->accessor();

        return $this->registry->all();
    }


    public function set(string $key, mixed $value): void
    {
        $this->mutator();

        $this->registry->set($key, $value);
    }


    public function mergeByDir($path, $environment)
    {
        $this->mutator();

        $this->registry->loadConfig($path, true);
        $this->registry->mergeEnvironment($path, $environment);
    }


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


    protected function mutator()
    {
        if ($this->finalized) {
            throw new Exception("Cannot mutate Config after finalized");
        }
    }


    protected function accessor()
    {
        if (!$this->finalized) {
            throw new Exception("Cannot access Config before finalized");
        }
    }


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
}
