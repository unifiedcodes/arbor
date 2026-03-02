<?php

namespace Arbor\view;


use Arbor\view\Document;
use Arbor\view\Component;
use RuntimeException;


/**
 * Manages document and component stacks for view rendering operations.
 */
class ViewStack
{
    /** @var Document|null The current document instance. */
    private ?Document $document = null;

    /** @var array<Component> Stack of components. */
    private array $components = [];

    /** @var array<Component> Stack of components currently being rendered. */
    private array $renderingComponents = [];


    /**
     * Sets the document for this view stack.
     *
     * @param Document $document The document to set.
     * @throws RuntimeException If a document is already set.
     */
    public function setDocument(Document $document): void
    {
        if ($this->document !== null) {
            throw new RuntimeException('Document already set in this ViewStack.');
        }

        $this->document = $document;
    }


    /**
     * Gets the current document.
     *
     * @return Document The document instance.
     * @throws RuntimeException If no document is set.
     */
    public function getDocument(): Document
    {
        if ($this->document === null) {
            throw new RuntimeException('No document set in ViewStack.');
        }

        return $this->document;
    }


    /**
     * Checks if a document is set.
     *
     * @return bool True if a document is set, false otherwise.
     */
    public function hasDocument(): bool
    {
        return $this->document !== null;
    }


    /**
     * Pushes a component onto the component stack.
     *
     * @param Component $component The component to push.
     */
    public function pushComponent(Component $component): void
    {
        $this->components[] = $component;
    }


    /**
     * Removes and returns the top component from the stack.
     *
     * @return Component The popped component.
     * @throws RuntimeException If the component stack is empty.
     */
    public function popComponent(): Component
    {
        if (empty($this->components)) {
            throw new RuntimeException('No components in stack.');
        }

        return array_pop($this->components);
    }


    /**
     * Gets the top component without removing it.
     *
     * @return Component|null The current component or null if stack is empty.
     */
    public function currentComponent(): ?Component
    {
        return $this->components[array_key_last($this->components)] ?? null;
    }


    /**
     * Returns all components in the stack.
     *
     * @return array<Component> The components array.
     */
    public function allComponents(): array
    {
        return $this->components;
    }


    /**
     * Checks if the component stack has any components.
     *
     * @return bool True if components exist, false otherwise.
     */
    public function hasComponents(): bool
    {
        return !empty($this->components);
    }


    /**
     * Clears all components from the stack.
     */
    public function clearComponents(): void
    {
        $this->components = [];
    }


    /**
     * Resets the entire view stack to initial state.
     */
    public function reset(): void
    {
        $this->document = null;
        $this->components = [];
        $this->renderingComponents = [];
    }


    /**
     * Returns the number of components in the stack.
     *
     * @return int The component count.
     */
    public function count(): int
    {
        return count($this->components);
    }


    // Rendering stack methods.

    /**
     * Pushes a component onto the rendering stack.
     *
     * @param Component $component The component to push.
     */
    public function pushRendering(Component $component): void
    {
        $this->renderingComponents[] = $component;
    }

    /**
     * Removes and returns the top component from the rendering stack.
     *
     * @return Component The popped component.
     * @throws RuntimeException If the rendering stack is empty.
     */
    public function popRendering(): Component
    {
        if (empty($this->renderingComponents)) {
            throw new RuntimeException('No rendering components in stack.');
        }

        return array_pop($this->renderingComponents);
    }


    /**
     * Gets the top component from the rendering stack without removing it.
     *
     * @return Component|null The current rendering component or null if stack is empty.
     */
    public function currentRendering(): ?Component
    {
        return $this->renderingComponents[array_key_last($this->renderingComponents)] ?? null;
    }
}
