<?php

namespace Arbor\view;

use Arbor\support\path\Uri;
use RuntimeException;

/**
 * Represents an HTML document definition.
 *
 * This class acts as a mutable builder for document-level
 * configuration such as:
 * - Language and charset
 * - Title and meta tags
 * - Styles and scripts (external + inline)
 * - HTML and body attributes
 * - Base href
 * - CSP nonce
 *
 * It is later normalized into a renderable Html object.
 */
final class Document
{
    private string $lang = 'en';
    private ?string $title = null;
    private string $charset = 'UTF-8';
    private array $meta = [];
    private array $htmlAttributes = [];
    private array $bodyAttributes = [];
    private array $styles = [];
    private array $inlineStyles = [];
    private array $scripts = [];
    private array $inlineScripts = [];
    private array $links = [];
    private string|Uri|null $baseHref = null;
    private ?string $nonce = null;

    /**
     * Allowed script placements.
     */
    private const SCRIPT_PLACEMENTS = ['head', 'body'];

    /**
     * @param Component $component Root component of the document.
     */
    public function __construct(
        private Component $component,
    ) {
        $this->scripts = array_fill_keys($this->scriptPlacements(), []);
        $this->inlineScripts = array_fill_keys($this->scriptPlacements(), []);
    }

    /**
     * Get root component.
     */
    public function component(): Component
    {
        return $this->component;
    }

    /**
     * Set document language.
     */
    public function lang(string $lang): static
    {
        $this->lang = $lang;
        return $this;
    }

    /**
     * Get document language.
     */
    public function getLang(): string
    {
        return $this->lang;
    }

    /**
     * Set document title.
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Append string to existing title.
     */
    public function appendTitle(string $part): static
    {
        if ($this->title === null) {
            $this->title = $part;
        } else {
            $this->title .= $part;
        }

        return $this;
    }

    /**
     * Get document title.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Add external stylesheet.
     *
     * @param string|Uri $href
     * @param array $attributes
     */
    public function style(string|Uri $href, array $attributes = []): static
    {
        $this->styles[] = [
            'href' => $href,
            'attributes' => $attributes,
        ];

        return $this;
    }

    /**
     * Get external styles.
     */
    public function getStyles(): array
    {
        return $this->styles;
    }

    /**
     * Add external script.
     *
     * @param string|Uri $src
     * @param array $attributes
     * @param string $placement head|body
     *
     * @throws RuntimeException
     */
    public function script(
        string|Uri $src,
        array $attributes = [],
        string $placement = 'body'
    ): static {
        $this->assertScriptPlacement($placement);

        $this->scripts[$placement][] = [
            'src' => $src,
            'attributes' => $attributes,
        ];

        return $this;
    }

    /**
     * Get scripts by placement.
     *
     * @throws RuntimeException
     */
    public function getScripts(string $placement = 'body'): array
    {
        $this->assertScriptPlacement($placement);
        return $this->scripts[$placement] ?? [];
    }

    /**
     * Add meta tag.
     */
    public function meta(array $attributes): static
    {
        $this->meta[] = $attributes;
        return $this;
    }

    /**
     * Get meta tags.
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * Internal attribute setter with special handling for "class".
     */
    private function setAttribute(array &$target, string $name, string $value): void
    {
        if ($name === 'class') {
            $existing = $target['class'] ?? '';

            $classes = array_filter(
                explode(' ', $existing . ' ' . $value)
            );

            $target['class'] = implode(' ', array_unique($classes));
            return;
        }

        $target[$name] = $value;
    }

    /**
     * Add or merge HTML attribute.
     */
    public function htmlAttr(string $name, string $value): static
    {
        $this->setAttribute($this->htmlAttributes, $name, $value);
        return $this;
    }

    /**
     * Add multiple HTML attributes.
     */
    public function htmlAttrs(array $attributes): static
    {
        foreach ($attributes as $name => $value) {
            $this->setAttribute($this->htmlAttributes, $name, $value);
        }

        return $this;
    }

    /**
     * Get HTML attributes.
     */
    public function getHtmlAttributes(): array
    {
        return $this->htmlAttributes;
    }

    /**
     * Add or merge body attribute.
     */
    public function bodyAttr(string $name, string $value): static
    {
        $this->setAttribute($this->bodyAttributes, $name, $value);
        return $this;
    }

    /**
     * Add multiple body attributes.
     */
    public function bodyAttrs(array $attributes): static
    {
        foreach ($attributes as $name => $value) {
            $this->setAttribute($this->bodyAttributes, $name, $value);
        }

        return $this;
    }

    /**
     * Get body attributes.
     */
    public function getBodyAttributes(): array
    {
        return $this->bodyAttributes;
    }

    /**
     * Set charset.
     */
    public function charset(string $charset): static
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * Get charset.
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Add generic link tag.
     */
    public function link(array $attributes): static
    {
        $this->links[] = $attributes;
        return $this;
    }

    /**
     * Get link tags.
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * Set base href.
     */
    public function base(string|Uri $href): static
    {
        $this->baseHref = $href;
        return $this;
    }

    /**
     * Get base href.
     */
    public function getBase(): ?Uri
    {
        return $this->baseHref;
    }

    /**
     * Convenience method to add viewport meta tag.
     */
    public function viewport(string $content = 'width=device-width, initial-scale=1'): static
    {
        $this->meta([
            'name' => 'viewport',
            'content' => $content,
        ]);

        return $this;
    }

    /**
     * Add inline style block.
     */
    public function inlineStyle(string $content, array $attributes = []): static
    {
        $this->inlineStyles[] = [
            'content' => $content,
            'attributes' => $attributes,
        ];

        return $this;
    }

    /**
     * Get inline styles.
     */
    public function getInlineStyles(): array
    {
        return $this->inlineStyles;
    }

    /**
     * Add inline script.
     *
     * @throws RuntimeException
     */
    public function inlineScript(
        string $content,
        array $attributes = [],
        string $placement = 'body'
    ): static {

        $this->assertScriptPlacement($placement);

        $this->inlineScripts[$placement][] = [
            'content' => $content,
            'attributes' => $attributes,
        ];

        return $this;
    }

    /**
     * Get inline scripts by placement.
     *
     * @throws RuntimeException
     */
    public function getInlineScripts(string $placement = 'body'): array
    {
        $this->assertScriptPlacement($placement);
        return $this->inlineScripts[$placement] ?? [];
    }

    /**
     * Set CSP nonce.
     */
    public function nonce(string $nonce): static
    {
        $this->nonce = $nonce;
        return $this;
    }

    /**
     * Get CSP nonce.
     */
    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    /**
     * Get allowed script placements.
     */
    public function scriptPlacements(): array
    {
        return self::SCRIPT_PLACEMENTS;
    }

    /**
     * Validate script placement.
     *
     * @throws RuntimeException
     */
    protected function assertScriptPlacement(string $placement)
    {
        if (!in_array($placement, self::SCRIPT_PLACEMENTS, true)) {
            throw new RuntimeException(
                "Invalid script placement '{$placement}'. Allowed: head, body."
            );
        }
    }

    /**
     * Convenience method for head script.
     */
    public function headScript(
        string|Uri $src,
        array $attributes = []
    ): static {
        return $this->script($src, $attributes, 'head');
    }

    /**
     * Convenience method for body script.
     */
    public function bodyScript(
        string|Uri $src,
        array $attributes = []
    ): static {
        return $this->script($src, $attributes, 'body');
    }

    /**
     * Convenience method for head inline script.
     */
    public function headInlineScript(
        string $content,
        array $attributes = []
    ): static {
        return $this->inlineScript($content, $attributes, 'head');
    }

    /**
     * Convenience method for body inline script.
     */
    public function bodyInlineScript(
        string $content,
        array $attributes = []
    ): static {
        return $this->inlineScript($content, $attributes, 'body');
    }
}
