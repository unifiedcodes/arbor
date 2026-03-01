<?php

namespace Arbor\view;

use Arbor\support\path\Uri;
use Arbor\view\SchemeRegistry;
use Arbor\view\ViewStack;
use Arbor\view\Renderer;
use Arbor\facades\Scope;
use RuntimeException;


class View
{
    private SchemeRegistry $schemes;
    private Renderer $renderer;


    public function __construct(
        private ?string $defaultScheme = null,
        private ?string $defaultAssetsScheme = null
    ) {
        $this->schemes = new SchemeRegistry();
        $this->renderer = new Renderer($this->schemes);

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


    public function normalizeViewUri(string|Uri $uri): Uri
    {
        if ($uri instanceof Uri) {
            return $uri;
        }

        if (!str_contains($uri, '://')) {

            // assert scheme is valid.
            if ($this->defaultScheme === null || $this->defaultScheme === '') {
                throw new RuntimeException(
                    "Cannot resolve view : '{$uri}' without scheme, no default view scheme configured."
                );
            }

            $uri = $this->defaultScheme . '://' . $uri;
        }

        return Uri::fromString($uri);
    }


    public function render(string|Uri $uri, array $data = []): string
    {
        $stack = Scope::get(ViewStack::class);

        $uri = $this->normalizeViewUri($uri);

        $component = new Component($uri, $data);

        $stack->setDocument(
            new Document($component, $this->defaultAssetsScheme)
        );

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

        $uri = $this->normalizeViewUri($uri);

        $component = new Component($uri, $data);

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

        $uri = $this->normalizeViewUri($uri);
        $component = new Component($uri, $data);

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
