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


    public function __construct()
    {
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
        $scheme = new Scheme($name, $root, $baseUrl);
        $this->schemes->register($scheme);
    }


    public function normalizeViewUri(string|Uri $uri): Uri
    {
        if ($uri instanceof Uri) {
            return $uri;
        }

        if (!str_contains($uri, '://')) {
            $uri = 'views://' . $uri;
        }

        return Uri::fromString($uri);
    }


    public function template(string|Uri $uri, array $data = []): string
    {
        $stack = Scope::get(ViewStack::class);

        $uri = $this->normalizeViewUri($uri);

        $component = new Component($uri, $data);

        $stack->setDocument(
            new Document($component)
        );

        try {
            return $this->renderer->template($stack);
        } finally {
            $stack->reset();
        }
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


    public function start(string|Uri $uri, array $data = []): void
    {
        $stack = Scope::get(ViewStack::class);

        $uri = $this->normalizeViewUri($uri);
        $component = new Component($uri, $data);

        $stack->pushComponent($component);

        $component->startDefault();
    }


    public function end(): void
    {
        $stack = Scope::get(ViewStack::class);

        $component = $stack->popComponent();

        $component->endDefault();

        $rendered = $this->renderer->component($component, $stack);

        echo $rendered;
    }


    public function slotStart(string $name): void
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


    public function push(string $name): void
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


    public function stack(string $name): string
    {
        $stack = Scope::get(ViewStack::class);
        $component = $stack->currentRendering();

        if (!$component) {
            return '';
        }

        return $component->stack($name);
    }
}
