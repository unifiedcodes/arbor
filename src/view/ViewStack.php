<?php

namespace Arbor\view;


use Arbor\view\Document;
use Arbor\view\Component;
use RuntimeException;


class ViewStack
{
    private ?Document $document = null;

    private array $components = [];
    private array $renderingComponents = [];


    public function setDocument(Document $document): void
    {
        if ($this->document !== null) {
            throw new RuntimeException('Document already set in this ViewStack.');
        }

        $this->document = $document;
    }


    public function getDocument(): Document
    {
        if ($this->document === null) {
            throw new RuntimeException('No document set in ViewStack.');
        }

        return $this->document;
    }


    public function hasDocument(): bool
    {
        return $this->document !== null;
    }


    public function pushComponent(Component $component): void
    {
        $this->components[] = $component;
    }


    public function popComponent(): Component
    {
        if (empty($this->components)) {
            throw new RuntimeException('No components in stack.');
        }

        return array_pop($this->components);
    }


    public function currentComponent(): ?Component
    {
        return $this->components[array_key_last($this->components)] ?? null;
    }


    public function allComponents(): array
    {
        return $this->components;
    }


    public function hasComponents(): bool
    {
        return !empty($this->components);
    }


    public function clearComponents(): void
    {
        $this->components = [];
    }


    public function reset(): void
    {
        $this->document = null;
        $this->components = [];
        $this->renderingComponents = [];
    }


    public function count(): int
    {
        return count($this->components);
    }


    // rendering stack methods.

    public function pushRendering(Component $component): void
    {
        $this->renderingComponents[] = $component;
    }

    public function popRendering(): Component
    {
        if (empty($this->renderingComponents)) {
            throw new RuntimeException('No rendering components in stack.');
        }

        return array_pop($this->renderingComponents);
    }


    public function currentRendering(): ?Component
    {
        return $this->renderingComponents[array_key_last($this->renderingComponents)] ?? null;
    }
}
