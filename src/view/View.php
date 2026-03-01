<?php

namespace Arbor\view;

use Arbor\support\path\Uri;
use Arbor\view\SchemeRegistry;
use Arbor\view\ViewStack;
use Arbor\view\Renderer;
use Arbor\facades\Scope;
use RuntimeException;
use Arbor\view\presets\PresetInterface;
use Arbor\view\presets\ClosurePreset;
use Closure;
use InvalidArgumentException;


class View
{
    private SchemeRegistry $schemes;
    private Renderer $renderer;
    private array $pendingPresets = [];


    public function __construct(
        bool $isDebug = false,
        private ?string $defaultScheme = 'view',
        private ?string $defaultAssetsScheme = 'asset'
    ) {
        $this->schemes = new SchemeRegistry($isDebug);

        $this->renderer = new Renderer(
            $this->schemes,
            $defaultAssetsScheme
        );

        Scope::set(ViewStack::class, new ViewStack());
    }


    public function aliasFacade(string $classname): void
    {
        if (!class_exists('View', false)) {
            class_alias(
                $classname,
                'View'
            );
        }
    }


    public function registerScheme(string $name, string $root, ?string $baseUrl = null): void
    {
        $this->schemes->register($name, $root, $baseUrl);
    }


    public function asset(string|Uri $uri): string
    {
        return $this->schemes->resolveAsset(
            $uri,
            $this->defaultAssetsScheme
        );
    }


    public function preset(PresetInterface|Closure ...$presets): static
    {
        foreach ($presets as $preset) {
            if ($preset instanceof Closure) {
                $preset = new ClosurePreset($preset);
            }

            if (!$preset instanceof PresetInterface) {
                throw new InvalidArgumentException(
                    'Preset must implement PresetInterface or be a Closure.'
                );
            }

            $this->pendingPresets[] = $preset;
        }

        return $this;
    }


    protected function applyPresets(Document $document): void
    {
        if (empty($this->pendingPresets)) {
            return;
        }

        foreach ($this->pendingPresets as $preset) {
            $preset->apply($document);
        }

        $this->pendingPresets = [];
    }


    protected function getComponent(string|Uri $uri, array $data = []): Component
    {
        $uri = $this->schemes->normalize(
            $uri,
            $this->defaultScheme
        );

        return new Component($uri, $data);
    }


    public function render(string|Uri $uri, array $data = []): string
    {
        $stack = Scope::get(ViewStack::class);

        $document = new Document($this->getComponent($uri, $data));

        // apply all preset stacks.
        $this->applyPresets($document);

        // push in stack.
        $stack->setDocument($document);

        try {
            return $this->renderer->document($stack);
        } finally {
            $stack->reset();
        }
    }


    public function document(): Document
    {
        $stack = Scope::get(ViewStack::class);

        if (!$stack->hasDocument()) {
            throw new RuntimeException('no document set');
        }

        return $stack->getDocument();
    }


    public function component(string|Uri $uri, array $data = []): string
    {
        $stack = Scope::get(ViewStack::class);

        $component = $this->getComponent($uri, $data);

        $stack->pushComponent($component);

        try {
            return $this->renderer->component(
                $component,
                $stack
            );
        } finally {
            $stack->popComponent();
        }
    }


    public function startComponent(string|Uri $uri, array $data = []): void
    {
        $stack = Scope::get(ViewStack::class);

        $component = $this->getComponent($uri, $data);

        $stack->pushComponent($component);

        $component->startDefault();
    }


    public function endComponent(): void
    {
        $stack = Scope::get(ViewStack::class);

        $component = $stack->popComponent();

        $component->endDefault();

        $rendered = $this->renderer->component($component, $stack);

        echo $rendered;
    }


    public function startSlot(string $name): void
    {
        $stack = Scope::get(ViewStack::class);

        $component = $stack->currentComponent();

        if (!$component) {
            throw new RuntimeException('No active component for slot.');
        }

        $component->startSlot($name);
    }


    public function endSlot(): void
    {
        $stack = Scope::get(ViewStack::class);

        $component = $stack->currentComponent();

        if (!$component) {
            throw new RuntimeException('No active component.');
        }

        $component->endSlot();
    }


    public function slot(?string $name = null): string
    {
        $stack = Scope::get(ViewStack::class);

        $component = $stack->currentRendering();

        if (!$component) {
            return '';
        }

        return $component->slot($name ?? 'default');
    }


    public function hasSlot(string $name): bool
    {
        $stack = Scope::get(ViewStack::class);

        $component = $stack->currentRendering();

        if (!$component) {
            return false;
        }

        return $component->slot($name) !== '';
    }


    public function slotOr(string $name, string $fallback): string
    {
        $content = $this->slot($name);

        return $content !== '' ? $content : $fallback;
    }


    public function startPush(string $name): void
    {
        $stack = Scope::get(ViewStack::class);
        $component = $stack->currentComponent();

        if (!$component) {
            throw new RuntimeException('No active component for push.');
        }

        $component->startPush($name);
    }


    public function endPush(): void
    {
        $stack = Scope::get(ViewStack::class);
        $component = $stack->currentComponent();

        if (!$component) {
            throw new RuntimeException('No active component.');
        }

        $component->endPush();
    }
}
