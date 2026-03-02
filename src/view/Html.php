<?php

namespace Arbor\view;


/**
 * Immutable value object containing HTML document configuration.
 * Stores metadata, attributes, scripts, stylesheets, and other HTML document settings.
 */
final class Html
{
    /**
     * Constructor for the Html configuration object.
     *
     * @param string $lang The language attribute for the html tag.
     * @param string $charset The character encoding for the document.
     * @param array $htmlAttributes Associative array of custom attributes for the html tag.
     * @param array $bodyAttributes Associative array of custom attributes for the body tag.
     * @param string|null $nonce Optional Content Security Policy nonce for scripts and styles.
     * @param string|null $title The page title to display in the browser tab.
     * @param array $links Array of link tag configurations.
     * @param array $styles Array of external stylesheet configurations.
     * @param array $inlineStyles Array of inline stylesheet configurations.
     * @param array $scripts Array of external script configurations indexed by placement ('head' or 'body').
     * @param array $inlineScripts Array of inline script configurations indexed by placement ('head' or 'body').
     * @param array $meta Array of meta tag configurations.
     * @param string|null $baseHref Optional base href for relative URLs in the document.
     */
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


    /**
     * Gets the language attribute for the HTML tag.
     *
     * @return string The language code (e.g., 'en', 'fr').
     */
    public function lang(): string
    {
        return $this->lang;
    }


    /**
     * Gets the character encoding for the document.
     *
     * @return string The charset (e.g., 'UTF-8').
     */
    public function charset(): string
    {
        return $this->charset;
    }


    /**
     * Gets custom attributes for the HTML tag.
     *
     * @return array Associative array of HTML tag attributes.
     */
    public function htmlAttributes(): array
    {
        return $this->htmlAttributes;
    }


    /**
     * Gets custom attributes for the body tag.
     *
     * @return array Associative array of body tag attributes.
     */
    public function bodyAttributes(): array
    {
        return $this->bodyAttributes;
    }


    /**
     * Gets the Content Security Policy nonce value.
     *
     * @return string|null The nonce string or null if not set.
     */
    public function nonce(): ?string
    {
        return $this->nonce;
    }


    /**
     * Gets the page title.
     *
     * @return string|null The title or null if not set.
     */
    public function title(): ?string
    {
        return $this->title;
    }


    /**
     * Gets all link tag configurations.
     *
     * @return array Array of link configurations.
     */
    public function links(): array
    {
        return $this->links;
    }


    /**
     * Gets all external stylesheet configurations.
     *
     * @return array Array of stylesheet configurations with 'href' and optional 'attributes'.
     */
    public function styles(): array
    {
        return $this->styles;
    }


    /**
     * Gets all inline stylesheet configurations.
     *
     * @return array Array of inline stylesheet configurations with 'content' and optional 'attributes'.
     */
    public function inlineStyles(): array
    {
        return $this->inlineStyles;
    }


    /**
     * Gets all external script configurations indexed by placement.
     *
     * @return array Array of script configurations indexed by 'head' or 'body' with 'src' and optional 'attributes'.
     */
    public function scripts(): array
    {
        return $this->scripts;
    }


    /**
     * Gets all inline script configurations indexed by placement.
     *
     * @return array Array of inline script configurations indexed by 'head' or 'body' with 'content' and optional 'attributes'.
     */
    public function inlineScripts(): array
    {
        return $this->inlineScripts;
    }


    /**
     * Gets all meta tag configurations.
     *
     * @return array Array of meta tag attribute arrays.
     */
    public function meta(): array
    {
        return $this->meta;
    }


    /**
     * Gets the base href for relative URLs.
     *
     * @return string|null The base href URL or null if not set.
     */
    public function baseHref(): ?string
    {
        return $this->baseHref;
    }
}
