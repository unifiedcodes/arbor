<?php

namespace Arbor\view;

use Arbor\view\SchemeRegistry;
use Arbor\view\Html;
use RuntimeException;

/**
 * Responsible for converting a Document instance
 * into a fully normalized Html value object.
 *
 * This class:
 * - Resolves asset URIs using SchemeRegistry
 * - Deduplicates styles, scripts, meta and links
 * - Normalizes inline and external resources
 * - Applies default asset scheme when needed
 */
class DocumentNormalizer
{
    /**
     * @param SchemeRegistry $schemes Registry used to resolve asset URIs.
     * @param string|null $defaultAssetScheme Optional default scheme applied when resolving assets.
     */
    public function __construct(
        private SchemeRegistry $schemes,
        private ?string $defaultAssetScheme = null
    ) {}

    /**
     * Normalize a Document into a structured Html object.
     *
     * @param Document $document
     * @return Html
     */
    public function normalize(Document $document): Html
    {
        return new Html(
            lang: $document->getLang(),
            charset: $document->getCharset(),
            htmlAttributes: $document->getHtmlAttributes(),
            bodyAttributes: $document->getBodyAttributes(),
            nonce: $document->getNonce(),
            title: $document->getTitle(),
            styles: $this->styles($document),
            inlineStyles: $this->inlineStyles($document),
            scripts: $this->scripts($document),
            inlineScripts: $this->inlineScripts($document),
            meta: $this->meta($document),
            baseHref: $this->base($document),
            links: $this->links($document),
        );
    }

    /**
     * Push a value into a bucket only if it has not already been added.
     *
     * @param array  $bucket Destination array.
     * @param array  $seen   Identity lookup map.
     * @param string $identity Unique identity string.
     * @param mixed  $value Value to push.
     * @return void
     */
    private function pushUnique(array &$bucket, array &$seen, string $identity, mixed $value): void
    {
        if (isset($seen[$identity])) {
            return;
        }

        $seen[$identity] = true;
        $bucket[] = $value;
    }

    /**
     * Resolve and deduplicate link-style resources (styles & generic links).
     *
     * @param array $items
     * @return array
     *
     * @throws RuntimeException If attributes are not an array.
     */
    protected function resolveLinks(array $items): array
    {
        $normalized = [];
        $seen = [];

        foreach ($items as $attributes) {

            if (!is_array($attributes)) {
                throw new RuntimeException('Attributes must be array.');
            }

            if (isset($attributes['href'])) {

                $attributes['href'] = $this->schemes->resolveAsset(
                    $attributes['href'],
                    $this->defaultAssetScheme
                );
            }

            $identity = serialize($attributes);

            $this->pushUnique(
                bucket: $normalized,
                seen: $seen,
                identity: $identity,
                value: $attributes
            );
        }

        return $normalized;
    }

    /**
     * Normalize and resolve external stylesheets.
     *
     * @param Document $document
     * @return array
     */
    protected function styles(Document $document): array
    {
        return $this->resolveLinks($document->getStyles());
    }

    /**
     * Normalize and resolve generic link elements.
     *
     * @param Document $document
     * @return array
     */
    protected function links(Document $document): array
    {
        return $this->resolveLinks($document->getLinks());
    }

    /**
     * Normalize and deduplicate inline styles.
     *
     * @param Document $document
     * @return array
     *
     * @throws RuntimeException If inline style content is not a string.
     */
    protected function inlineStyles(Document $document): array
    {
        $normalized = [];
        $seen = [];

        foreach ($document->getInlineStyles() as $style) {

            $content = $style['content'] ?? '';
            $attributes = $style['attributes'] ?? [];

            if (!is_string($content)) {
                throw new RuntimeException('Inline style content must be string.');
            }

            $identity = md5($content) . '-' . serialize($attributes);

            $this->pushUnique(
                bucket: $normalized,
                seen: $seen,
                identity: $identity,
                value: [
                    'content' => $content,
                    'attributes' => $attributes,
                ]
            );
        }

        return $normalized;
    }

    /**
     * Normalize and resolve external scripts grouped by placement.
     *
     * @param Document $document
     * @return array
     */
    protected function scripts(Document $document): array
    {
        $normalized = array_fill_keys($document->scriptPlacements(), []);
        $seen = [];

        foreach ($document->scriptPlacements() as $placement) {

            foreach ($document->getScripts($placement) as $script) {

                $uri = $script['src'] ?? null;
                $attributes = $script['attributes'] ?? [];

                $identity = $placement . '-' . $uri . '-' . serialize($attributes);

                $src = $this->schemes->resolveAsset($uri, $this->defaultAssetScheme);

                $this->pushUnique(
                    bucket: $normalized[$placement],
                    seen: $seen,
                    identity: $identity,
                    value: [
                        'src' => $src,
                        'attributes' => $attributes,
                    ]
                );
            }
        }

        return $normalized;
    }

    /**
     * Normalize and deduplicate inline scripts grouped by placement.
     *
     * @param Document $document
     * @return array
     *
     * @throws RuntimeException If inline script content is not a string.
     */
    protected function inlineScripts(Document $document): array
    {
        $normalized = array_fill_keys($document->scriptPlacements(), []);
        $seen = [];

        foreach ($document->scriptPlacements() as $placement) {

            foreach ($document->getInlineScripts($placement) as $script) {

                $content = $script['content'] ?? '';
                $attributes = $script['attributes'] ?? [];

                if (!is_string($content)) {
                    throw new RuntimeException('Inline script content must be string.');
                }

                $identity = $placement . '-' . md5($content) . '-' . serialize($attributes);

                $this->pushUnique(
                    bucket: $normalized[$placement],
                    seen: $seen,
                    identity: $identity,
                    value: [
                        'content' => $content,
                        'attributes' => $attributes,
                    ]
                );
            }
        }

        return $normalized;
    }

    /**
     * Normalize and deduplicate meta tags.
     *
     * @param Document $document
     * @return array
     *
     * @throws RuntimeException If meta attributes are not an array.
     */
    protected function meta(Document $document): array
    {
        $normalized = [];
        $seen = [];

        foreach ($document->getMeta() as $attributes) {

            if (!is_array($attributes)) {
                throw new RuntimeException('Meta attributes must be array.');
            }

            $identity = serialize($attributes);

            $this->pushUnique(
                bucket: $normalized,
                seen: $seen,
                identity: $identity,
                value: $attributes
            );
        }

        return $normalized;
    }

    /**
     * Resolve base href if defined.
     *
     * @param Document $document
     * @return string|null
     */
    protected function base(Document $document): ?string
    {
        $base = $document->getBase();

        if ($base === null) {
            return null;
        }

        return $this->schemes->resolveAsset($base, $this->defaultAssetScheme);
    }
}
