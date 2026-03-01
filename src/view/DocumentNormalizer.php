<?php

namespace Arbor\view;


use Arbor\view\SchemeRegistry;
use Arbor\support\path\Uri;
use Arbor\view\Html;
use RuntimeException;


class DocumentNormalizer
{
    public function __construct(private SchemeRegistry $schemes) {}


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


    protected function resolveAsset(Uri $uri): string
    {
        $schemeName = $uri->scheme();

        if (in_array($schemeName, ['http', 'https'], true)) {
            return (string) $uri;
        }

        $scheme = $this->schemes->get($schemeName);

        if (!$scheme) {
            throw new RuntimeException("Unregistered scheme: '{$schemeName}'");
        }

        if (!$scheme->isPublic()) {
            throw new RuntimeException(
                "Scheme '{$schemeName}' is not public."
            );
        }

        $relative = ltrim($uri->path(), '/');

        $file = normalizeFilePath($scheme->root() . $relative);

        if (!is_file($file)) {
            throw new RuntimeException("Asset file not found: '{$file}'");
        }

        return rtrim($scheme->baseUrl(), '/') . '/' . $relative;
    }


    private function pushUnique(array &$bucket, array &$seen, string $identity, mixed $value): void
    {
        if (isset($seen[$identity])) {
            return;
        }

        $seen[$identity] = true;
        $bucket[] = $value;
    }


    protected function resolveLinks(array $items): array
    {
        $normalized = [];
        $seen = [];

        foreach ($items as $attributes) {

            if (!is_array($attributes)) {
                throw new RuntimeException('Attributes must be array.');
            }

            if (isset($attributes['href'])) {

                $href = $attributes['href'];

                if (!$href instanceof Uri) {
                    throw new RuntimeException('Href must be instance of Uri.');
                }

                $attributes['href'] = $this->resolveAsset($href);
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


    protected function styles(Document $document): array
    {
        return $this->resolveLinks($document->getStyles());
    }


    protected function links(Document $document): array
    {
        return $this->resolveLinks($document->getLinks());
    }


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


    protected function scripts(Document $document): array
    {
        $normalized = array_fill_keys($document->scriptPlacements(), []);
        $seen = [];

        foreach ($document->scriptPlacements() as $placement) {

            foreach ($document->getScripts($placement) as $script) {

                $uri = $script['src'] ?? null;
                $attributes = $script['attributes'] ?? [];

                if (!$uri instanceof Uri) {
                    throw new RuntimeException('Script src must be instance of Uri.');
                }

                $identity = $placement . '-' . (string) $uri . '-' . serialize($attributes);

                $src = $this->resolveAsset($uri);

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


    protected function base(Document $document): ?string
    {
        $base = $document->getBase();

        if ($base === null) {
            return null;
        }

        if (!$base instanceof Uri) {
            throw new RuntimeException('Base must be instance of Uri.');
        }

        return $this->resolveAsset($base);
    }
}
