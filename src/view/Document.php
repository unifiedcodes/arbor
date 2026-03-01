<?php

namespace Arbor\view;


use Arbor\support\path\Uri;
use RuntimeException;


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
    private ?Uri $baseHref = null;
    private ?string $nonce = null;

    private const SCRIPT_PLACEMENTS = ['head', 'body'];

    public function __construct(
        private Component $component,
    ) {
        $this->scripts = array_fill_keys($this->scriptPlacements(), []);
        $this->inlineScripts = array_fill_keys($this->scriptPlacements(), []);
    }


    public function component(): Component
    {
        return $this->component;
    }


    public function lang(string $lang): static
    {
        $this->lang = $lang;
        return $this;
    }


    public function getLang(): string
    {
        return $this->lang;
    }


    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function appendTitle(string $part): static
    {
        if ($this->title === null) {
            $this->title = $part;
        } else {
            $this->title .= $part;
        }

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }


    public function style(string|Uri $href, array $attributes = []): static
    {
        $this->styles[] = [
            'href' => $href,
            'attributes' => $attributes,
        ];

        return $this;
    }


    public function getStyles(): array
    {
        return $this->styles;
    }


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


    public function getScripts(string $placement = 'body'): array
    {
        $this->assertScriptPlacement($placement);

        return $this->scripts[$placement] ?? [];
    }


    public function meta(array $attributes): static
    {
        $this->meta[] = $attributes;
        return $this;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }


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


    public function htmlAttr(string $name, string $value): static
    {
        $this->setAttribute($this->htmlAttributes, $name, $value);
        return $this;
    }


    public function htmlAttrs(array $attributes): static
    {
        foreach ($attributes as $name => $value) {
            $this->setAttribute($this->htmlAttributes, $name, $value);
        }

        return $this;
    }


    public function getHtmlAttributes(): array
    {
        return $this->htmlAttributes;
    }


    public function bodyAttr(string $name, string $value): static
    {
        $this->setAttribute($this->bodyAttributes, $name, $value);
        return $this;
    }


    public function bodyAttrs(array $attributes): static
    {
        foreach ($attributes as $name => $value) {
            $this->setAttribute($this->bodyAttributes, $name, $value);
        }

        return $this;
    }


    public function getBodyAttributes(): array
    {
        return $this->bodyAttributes;
    }


    public function charset(string $charset): static
    {
        $this->charset = $charset;

        return $this;
    }


    public function getCharset(): string
    {
        return $this->charset;
    }


    public function link(array $attributes): static
    {
        $this->links[] = $attributes;

        return $this;
    }

    public function getLinks(): array
    {
        return $this->links;
    }


    public function base(string|Uri $href): static
    {
        $this->baseHref = $href;
        return $this;
    }

    public function getBase(): ?Uri
    {
        return $this->baseHref;
    }


    public function viewport(string $content = 'width=device-width, initial-scale=1'): static
    {
        $this->meta([
            'name' => 'viewport',
            'content' => $content,
        ]);

        return $this;
    }


    public function inlineStyle(string $content, array $attributes = []): static
    {
        $this->inlineStyles[] = [
            'content' => $content,
            'attributes' => $attributes,
        ];

        return $this;
    }

    public function getInlineStyles(): array
    {
        return $this->inlineStyles;
    }


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


    public function getInlineScripts(string $placement = 'body'): array
    {
        $this->assertScriptPlacement($placement);
        return $this->inlineScripts[$placement] ?? [];
    }


    public function nonce(string $nonce): static
    {
        $this->nonce = $nonce;
        return $this;
    }

    public function getNonce(): ?string
    {
        return $this->nonce;
    }


    public function scriptPlacements(): array
    {
        return self::SCRIPT_PLACEMENTS;
    }


    protected function assertScriptPlacement(string $placement)
    {
        if (!in_array($placement, self::SCRIPT_PLACEMENTS, true)) {
            throw new RuntimeException(
                "Invalid script placement '{$placement}'. Allowed: head, body."
            );
        }
    }
}
