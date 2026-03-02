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


/**
 * Main view management class for rendering components and documents.
 * Handles component rendering, asset resolution, presets, slots, and push stacks.
 */
class View
{
    /** @var SchemeRegistry Registry for managing URI schemes and paths. */
    private SchemeRegistry $schemes;

    /** @var Renderer The renderer instance for processing components and documents. */
    private Renderer $renderer;

    /** @var array<PresetInterface> Presets pending application to the next document. */
    private array $pendingPresets = [];


    /**
     * Constructor for the View class.
     *
     * @param bool $isDebug Whether debug mode is enabled.
     * @param string|null $defaultScheme The default URI scheme for components (default: 'view').
     * @param string|null $defaultAssetsScheme The default URI scheme for assets (default: 'asset').
     * @param PresetInterface|null $defaultPreset The default preset to apply to all documents.
     */
    public function __construct(
        bool $isDebug = false,
        private ?string $defaultScheme = 'view',
        private ?string $defaultAssetsScheme = 'asset',
        private ?PresetInterface $defaultPreset = null,
    ) {
        $this->schemes = new SchemeRegistry($isDebug);

        $this->renderer = new Renderer(
            $this->schemes,
            $defaultAssetsScheme
        );

        Scope::set(ViewStack::class, new ViewStack());
    }


    /**
     * Creates a class alias for the View class if it doesn't already exist.
     *
     * @param string $classname The fully qualified class name to alias from.
     */
    public function aliasFacade(string $classname): void
    {
        if (!class_exists('View', false)) {
            class_alias(
                $classname,
                'View'
            );
        }
    }


    /**
     * Registers a new URI scheme with a root path and optional base URL.
     *
     * @param string $name The name of the scheme.
     * @param string $root The root directory path for the scheme.
     * @param string|null $baseUrl The optional base URL for the scheme.
     */
    public function registerScheme(string $name, string $root, ?string $baseUrl = null): void
    {
        $this->schemes->register($name, $root, $baseUrl);
    }


    /**
     * Resolves an asset URI to its final URL.
     *
     * @param string|Uri $uri The asset URI to resolve.
     * @return string The resolved asset URL.
     */
    public function asset(string|Uri $uri): string
    {
        return $this->schemes->resolveAsset(
            $uri,
            $this->defaultAssetsScheme
        );
    }


    /**
     * Adds one or more presets to be applied to the next rendered document.
     *
     * @param PresetInterface|Closure ...$presets Presets or closures to apply.
     * @return static Returns this instance for method chaining.
     * @throws InvalidArgumentException If a preset is neither a PresetInterface nor a Closure.
     */
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


    /**
     * Applies default and pending presets to a document.
     *
     * @param Document $document The document to apply presets to.
     */
    protected function applyPresets(Document $document): void
    {
        if ($this->defaultPreset !== null) {
            $this->defaultPreset->apply($document);
        }

        foreach ($this->pendingPresets as $preset) {
            $preset->apply($document);
        }

        $this->pendingPresets = [];
    }


    /**
     * Creates a Component instance with normalized URI and data.
     *
     * @param string|Uri $uri The component URI.
     * @param array $data Optional data to pass to the component.
     * @return Component The created component instance.
     */
    protected function getComponent(string|Uri $uri, array $data = []): Component
    {
        $uri = $this->schemes->normalize(
            $uri,
            $this->defaultScheme
        );

        return new Component($uri, $data);
    }


    /**
     * Renders a document with the given URI and data.
     *
     * @param string|Uri $uri The component URI to render.
     * @param array $data Optional data to pass to the component.
     * @return string The rendered HTML output.
     */
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


    /**
     * Gets the currently active document from the stack.
     *
     * @return Document The active document.
     * @throws RuntimeException If no document is set.
     */
    public function document(): Document
    {
        $stack = Scope::get(ViewStack::class);

        if (!$stack->hasDocument()) {
            throw new RuntimeException('no document set');
        }

        return $stack->getDocument();
    }


    /**
     * Renders a single component and returns its HTML output.
     *
     * @param string|Uri $uri The component URI to render.
     * @param array $data Optional data to pass to the component.
     * @return string The rendered HTML output.
     */
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


    /**
     * Starts a component render block (use with endComponent()).
     *
     * @param string|Uri $uri The component URI to start.
     * @param array $data Optional data to pass to the component.
     */
    public function startComponent(string|Uri $uri, array $data = []): void
    {
        $stack = Scope::get(ViewStack::class);

        $component = $this->getComponent($uri, $data);

        $stack->pushComponent($component);

        $component->startDefault();
    }


    /**
     * Ends a component render block (pairs with startComponent()).
     */
    public function endComponent(): void
    {
        $stack = Scope::get(ViewStack::class);

        $component = $stack->popComponent();

        $component->endDefault();

        $rendered = $this->renderer->component($component, $stack);

        echo $rendered;
    }


    /**
     * Starts a named slot within the current component.
     *
     * @param string $name The name of the slot.
     * @throws RuntimeException If no active component exists.
     */
    public function startSlot(string $name): void
    {
        $stack = Scope::get(ViewStack::class);

        $component = $stack->currentComponent();

        if (!$component) {
            throw new RuntimeException('No active component for slot.');
        }

        $component->startSlot($name);
    }


    /**
     * Ends the current slot.
     *
     * @throws RuntimeException If no active component exists.
     */
    public function endSlot(): void
    {
        $stack = Scope::get(ViewStack::class);

        $component = $stack->currentComponent();

        if (!$component) {
            throw new RuntimeException('No active component.');
        }

        $component->endSlot();
    }


    /**
     * Gets the content of a named slot from the currently rendering component.
     *
     * @param string|null $name The slot name (defaults to 'default').
     * @return string The slot content or empty string if not found.
     */
    public function slot(?string $name = null): string
    {
        $stack = Scope::get(ViewStack::class);

        $component = $stack->currentRendering();

        if (!$component) {
            return '';
        }

        return $component->slot($name ?? 'default');
    }


    /**
     * Checks if a slot exists and has content.
     *
     * @param string $name The slot name to check.
     * @return bool True if the slot has content, false otherwise.
     */
    public function hasSlot(string $name): bool
    {
        $stack = Scope::get(ViewStack::class);

        $component = $stack->currentRendering();

        if (!$component) {
            return false;
        }

        return $component->slot($name) !== '';
    }


    /**
     * Gets slot content with a fallback value if empty.
     *
     * @param string $name The slot name.
     * @param string $fallback The fallback content if slot is empty.
     * @return string The slot content or fallback.
     */
    public function slotOr(string $name, string $fallback): string
    {
        $content = $this->slot($name);

        return $content !== '' ? $content : $fallback;
    }


    /**
     * Starts a named push section (content accumulation stack).
     *
     * @param string $name The name of the push section.
     * @throws RuntimeException If no active component exists.
     */
    public function startPush(string $name): void
    {
        $stack = Scope::get(ViewStack::class);
        $component = $stack->currentComponent();

        if (!$component) {
            throw new RuntimeException('No active component for push.');
        }

        $component->startPush($name);
    }


    /**
     * Ends the current push section.
     *
     * @throws RuntimeException If no active component exists.
     */
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
