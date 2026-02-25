<?php

namespace Arbor\view;

use Exception;
use InvalidArgumentException;
use Arbor\config\ConfigValue;

class Builder
{
    protected ?Builder $rootNode = null;

    protected string $title = '';

    protected string $charset = 'utf-8';

    protected string $lang = 'en';

    protected array $links = [];

    protected array $metas = [];

    protected array $scripts = [];

    protected array $inlineStyles = [];

    protected array $inlineScripts = [];

    protected array $bodyAttributes = [];

    protected array|string $body = [];

    protected array $toAppendBody = [];

    protected array $bindings = [];

    protected bool $autoEscapeContent = true;

    protected string $view_dir = '';

    public function __construct(
        #[ConfigValue('app.views_dir')]
        string $view_dir,
    ) {
        $this->view_dir = $view_dir;
    }

    public function setRoot(Builder $root): static
    {
        $this->rootNode = $root;
        return $this;
    }

    public function getRoot(): Builder
    {
        return $this->rootNode ?? $this;
    }

    public function set(string $key, mixed $value): static
    {
        value_set_at($this->bindings, $key, $value);
        return $this;
    }

    public function get(string|null $key = null, mixed $default = null): mixed
    {
        return value_at($this->bindings, $key, $default);
    }

    public function replace(array $bindings): static
    {
        $this->bindings = $bindings;
        return $this;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setCharset(string $charset): static
    {
        $this->charset = $charset;
        return $this;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function setLang(string $lang): static
    {
        $this->lang = $lang;
        return $this;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function addMeta(string $name, string $content): static
    {
        $metas = $this->getRoot()->getMetas();
        $metas[] = ['name' => $name, 'content' => $content];
        $this->getRoot()->setMetas($metas);

        return $this;
    }

    public function addOG(string $name, string $content): static
    {
        $metas = $this->getRoot()->getMetas();
        $metas[] = ['property' => $name, 'content' => $content];
        $this->getRoot()->setMetas($metas);

        return $this;
    }

    public function getMetas(): array
    {
        return $this->metas;
    }

    protected function setMetas(array $metas): void
    {
        $this->metas = $metas;
    }

    public function addLink(string $rel, string $href, array $attributes = []): static
    {
        $this->validateAssetURI($href);

        $links = $this->getRoot()->getLinks();
        $links[] = array_merge(['rel' => $rel, 'href' => $href], $attributes);
        $this->getRoot()->setLinks($links);

        return $this;
    }

    public function getLinks(): array
    {
        return $this->links;
    }

    protected function setLinks(array $links): void
    {
        $this->links = $links;
    }

    public function addBaseLink(string $href): static
    {
        $this->validateAssetURI($href);
        $this->addLink('base', $href);
        return $this;
    }

    public function addStyle(string $href, string $media = 'all', string $fromPath = ''): static
    {
        $href = $this->buildPath($href, $fromPath);
        $this->validateAssetURI($href);
        $this->addLink('stylesheet', $href, ['media' => $media]);

        return $this;
    }

    public function inlineStyle(string $style): static
    {
        $styles = $this->getRoot()->getInlineStyles();
        $styles[] = $style;
        $this->getRoot()->setInlineStyles($styles);

        return $this;
    }

    public function getInlineStyles(): array
    {
        return $this->inlineStyles;
    }

    protected function setInlineStyles(array $styles): void
    {
        $this->inlineStyles = $styles;
    }

    public function addScript(string $src, array $attributes = [], string $fromPath = ''): static
    {
        $src = $this->buildPath($src, $fromPath);
        $this->validateAssetURI($src);

        $scripts = $this->getRoot()->getScripts();
        $scripts[] = array_merge(['src' => $src], $attributes);
        $this->getRoot()->setScripts($scripts);

        return $this;
    }

    public function getScripts(): array
    {
        return $this->scripts;
    }

    protected function setScripts(array $scripts): void
    {
        $this->scripts = $scripts;
    }

    public function inlineScript(string $script): static
    {
        $inlineScripts = $this->getRoot()->getInlineScripts();
        $inlineScripts[] = $script;
        $this->getRoot()->setInlineScripts($inlineScripts);

        return $this;
    }

    public function getInlineScripts(): array
    {
        return $this->inlineScripts;
    }

    protected function setInlineScripts(array $scripts)
    {
        $this->inlineScripts = $scripts;
    }

    public function setBody(callable|string|array $content, string $type = 'html'): static
    {
        $this->body = ['type' => $type, 'content' => $content];
        return $this;
    }

    public function setTemplate(string $templateName): static
    {
        $this->setBody($templateName, 'template');
        return $this;
    }

    public function setHTMLBody(string $html): static
    {
        $this->setBody($html, 'html');
        return $this;
    }

    public function useController(string $controller): static
    {
        $this->setBody($controller, 'controller');
        return $this;
    }

    public function setStringBody(string $string): static
    {
        $this->setBody($string, 'string');
        return $this;
    }

    public function getBody(): array|string
    {
        return $this->body;
    }

    public function appendBodyContent(string $html): static
    {
        $chunks = $this->getRoot()->getToAppendBody();
        $chunks[] = $html;
        $this->getRoot()->setToAppendBody($chunks);
        return $this;
    }

    public function getToAppendBody(): array
    {
        return $this->toAppendBody;
    }

    protected function setToAppendBody(array $chunks): void
    {
        $this->toAppendBody = $chunks;
    }

    public function addBodyAttribute(string $key, string $value): static
    {
        $attributes = $this->getRoot()->getBodyAttributes();
        $attributes[$key] = $value;
        $this->getRoot()->setBodyAttributes($attributes);
        return $this;
    }

    public function getBodyAttributes(): array
    {
        return $this->bodyAttributes;
    }

    protected function setBodyAttributes(array $attributes): void
    {
        $this->bodyAttributes = $attributes;
    }

    public function setAutoEscapeContent(bool $enabled): static
    {
        $this->getRoot()->autoEscapeContent = $enabled;
        return $this;
    }

    public function getAutoEscapeContent(): bool
    {
        return $this->getRoot()->autoEscapeContent;
    }

    public function getContext(): array
    {
        return [
            'title'          => $this->getTitle(),
            'bodyAttributes' => $this->getBodyAttributes(),
            'bindings'       => $this->bindings,
            'autoEscape'     => $this->getAutoEscapeContent()
        ];
    }

    public function with(array|string $data, $value = null): static
    {
        $numArgs = func_num_args();

        if ($numArgs === 1) {
            if (!is_array($data)) {
                throw new InvalidArgumentException('with(): single-argument calls expects an array.');
            }

            $shared = $this->bindings['shared'] ?? null;

            $this->bindings = $data;

            if ($shared !== null) {
                $this->bindings['shared'] = $shared;
            }

            return $this;
        }

        if ($numArgs >= 2) {
            if (!is_string($data)) {
                throw new InvalidArgumentException('with(): when passing a value the first argument must be a string key.');
            }

            return $this->set($data, $value);
        }

        throw new InvalidArgumentException('with(): unexpected argument state.');
    }

    protected function validateAssetURI(string $href): void
    {
        if (!filter_var($href, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid asset path or URL: $href");
        }
    }

    protected function buildPath(string $toAppend, ?string $pathVar = null): string
    {
        if (empty($pathVar)) {
            return $toAppend;
        }

        $basePath = $this->getRoot()->get($pathVar);

        if (empty($basePath)) {
            throw new Exception(
                "Failed to build path using '{$pathVar}' - ensure the path variable is bound before use."
            );
        }

        return $basePath . $toAppend;
    }

    public function toHtml(): string
    {
        return (new Renderer($this, $this->view_dir))->toHTML();
    }

    public function toPartialHtml(): string
    {
        return (new Renderer($this, $this->view_dir))->renderBody();
    }
}
