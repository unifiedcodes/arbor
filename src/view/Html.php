<?php

namespace Arbor\view;


final class Html
{
    public function __construct(
        private string $lang,
        private string $charset,
        private array $htmlAttributes,
        private array $bodyAttributes,
        private ?string $nonce = null,
        private ?string $title = null,
        private array $links = [],
        private array $styles = [],
        private array $inlineStyles = [],
        private array $scripts = [],
        private array $inlineScripts = [],
        private array $meta = [],
        private ?string $baseHref = null,
    ) {}


    public function lang(): string
    {
        return $this->lang;
    }


    public function charset(): string
    {
        return $this->charset;
    }


    public function htmlAttributes(): array
    {
        return $this->htmlAttributes;
    }


    public function bodyAttributes(): array
    {
        return $this->bodyAttributes;
    }


    public function nonce(): ?string
    {
        return $this->nonce;
    }


    public function title(): ?string
    {
        return $this->title;
    }


    public function links(): array
    {
        return $this->links;
    }


    public function styles(): array
    {
        return $this->styles;
    }


    public function inlineStyles(): array
    {
        return $this->inlineStyles;
    }


    public function scripts(): array
    {
        return $this->scripts;
    }


    public function inlineScripts(): array
    {
        return $this->inlineScripts;
    }


    public function meta(): array
    {
        return $this->meta;
    }


    public function baseHref(): ?string
    {
        return $this->baseHref;
    }
}
