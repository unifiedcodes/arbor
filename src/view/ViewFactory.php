<?php

namespace Arbor\view;

use Closure;
use Exception;
use Arbor\view\Builder;
use Arbor\fragment\Fragment;
use Arbor\config\ConfigValue;

final class ViewFactory
{
    protected array $presets = [];

    protected array $resolved = [];

    protected string $view_dir;

    protected Fragment $fragment;

    protected string|null $defaultPreset = null;

    protected ?string $extendingPreset = null;

    protected array $config = [];

    protected array $sharedData = [];

    public function __construct(
        #[ConfigValue('app.views_dir')]
        string $view_dir,
        Fragment $fragment,
    ) {
        $this->view_dir = $view_dir;
        $this->fragment = $fragment;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function setSharedData(array $data): void
    {
        $this->sharedData = $data;
    }

    public function setPreset(string $name, Closure $configurator): void
    {
        if (!isset($this->presets[$name])) {
            $this->presets[$name] = [];
        }
        $this->presets[$name][] = $configurator;
    }

    public function setDefaultPreset(string|Closure $preset): void
    {
        if ($preset instanceof Closure) {
            $this->setPreset('default', $preset);
            $this->defaultPreset = 'default';
            return;
        }

        if (!isset($this->presets[$preset])) {
            throw new Exception("Preset with name '{$preset}' not found");
        }

        $this->defaultPreset = $preset;
    }

    public function extendPreset(string $parentName, string $newName, Closure $extraConfigurator): void
    {
        if (!isset($this->presets[$parentName])) {
            throw new Exception("Cannot extend non-existing preset '{$parentName}'");
        }

        $this->presets[$newName] = array_merge(
            $this->presets[$parentName],
            [$extraConfigurator]
        );
    }

    public function getPreset(?string $name = null): Builder
    {
        if ($name === null) {
            return $this->getDefaultPreset();
        }

        if (!isset($this->presets[$name])) {
            throw new Exception("Preset with name '{$name}' not found");
        }

        return $this->resolveConfiguratorStack($this->presets[$name]);
    }

    public function getDefaultPreset(): Builder
    {
        if (!$this->defaultPreset) {
            throw new Exception("No default preset defined. Use setDefaultPreset() first.");
        }

        return $this->resolveConfiguratorStack($this->presets[$this->defaultPreset]);
    }

    protected function resolveConfiguratorStack(array $configurators): Builder
    {
        $builder = new Builder($this->view_dir, $this->fragment);

        $builder->set('shared', $this->sharedData);

        foreach ($configurators as $configurator) {
            $configurator($builder, $this->config);
        }

        return $builder;
    }

    public function set(string $name, Closure $configurator): void
    {
        $this->setPreset($name, $configurator);
    }

    public function default(string|Closure $preset): void
    {
        $this->setDefaultPreset($preset);
    }

    public function get(?string $name = null): Builder
    {
        return $this->getPreset($name);
    }

    public function extends(string $parentName, string $newName, Closure $extraConfigurator): void
    {
        $this->extendPreset($parentName, $newName, $extraConfigurator);
    }
}
