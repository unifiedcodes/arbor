<?php

namespace Arbor\view;

use Arbor\view\Html;

final class HtmlRenderer
{
    public function __construct(
        private Html $html,
        private string $body,
    ) {}


    public function render(): string
    {
        return "<!DOCTYPE html>\n"
            . $this->openHtml()
            . $this->head()
            . $this->body()
            . "</html>";
    }


    private function openHtml(): string
    {
        $attributes = array_merge(
            ['lang' => $this->html->lang()],
            $this->html->htmlAttributes()
        );

        return "<html" . $this->renderAttributes($attributes) . ">\n";
    }


    private function head(): string
    {
        return "<head>\n"
            . $this->metaCharset()
            . $this->base()
            . $this->meta()
            . $this->title()
            . $this->links()
            . $this->styles()
            . $this->inlineStyles()
            . $this->renderScripts('head')
            . $this->renderInlineScripts('head')
            . "</head>\n";
    }


    private function body(): string
    {
        $attributes = $this->renderAttributes(
            $this->html->bodyAttributes()
        );

        return "<body{$attributes}>\n"
            . $this->body
            . "\n"
            . $this->renderScripts('body')
            . $this->renderInlineScripts('body')
            . "</body>\n";
    }


    private function metaCharset(): string
    {
        $charset = $this->escape($this->html->charset());

        return "<meta charset=\"{$charset}\">\n";
    }


    private function title(): string
    {
        $title = $this->html->title();

        if ($title === null) {
            return '';
        }

        return "<title>"
            . $this->escape($title)
            . "</title>\n";
    }


    private function styles(): string
    {
        $output = '';
        $nonce  = $this->html->nonce();

        foreach ($this->html->styles() as $style) {
            $href = $this->escape($style['href']);

            $attributes = $style['attributes'] ?? [];

            if ($nonce !== null) {
                $attributes['nonce'] = $nonce;
            }

            $output .= "<link rel=\"stylesheet\" href=\"{$href}\""
                . $this->renderAttributes($attributes)
                . ">\n";
        }

        return $output;
    }


    private function inlineStyles(): string
    {
        $output = '';
        $nonce  = $this->html->nonce();

        foreach ($this->html->inlineStyles() as $style) {
            $content = $style['content'] ?? '';
            $attributes = $style['attributes'] ?? [];

            if ($nonce !== null) {
                $attributes['nonce'] = $nonce;
            }

            $output .= "<style"
                . $this->renderAttributes($attributes)
                . ">\n"
                . $content
                . "\n</style>\n";
        }

        return $output;
    }


    private function renderAttributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $name => $value) {
            $name = $this->escape($name);

            if (is_bool($value)) {
                if ($value) {
                    $html .= " {$name}";
                }
                continue;
            }

            if ($value === null) {
                continue;
            }

            $html .= " {$name}=\""
                . $this->escape((string) $value)
                . "\"";
        }

        return $html;
    }


    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }


    private function base(): string
    {
        $href = $this->html->baseHref();

        if ($href === null) {
            return '';
        }

        return "<base href=\""
            . $this->escape($href)
            . "\">\n";
    }


    private function meta(): string
    {
        $output = '';

        foreach ($this->html->meta() as $meta) {
            $attributes = $this->renderAttributes($meta);
            $output .= "<meta{$attributes}>\n";
        }

        return $output;
    }


    private function links(): string
    {
        $output = '';

        foreach ($this->html->links() as $link) {
            $attributes = $this->renderAttributes($link);
            $output .= "<link{$attributes}>\n";
        }

        return $output;
    }


    private function renderScripts(string $placement): string
    {
        $output = '';
        $nonce  = $this->html->nonce();

        $scriptsByPlacement = $this->html->scripts();

        if (!isset($scriptsByPlacement[$placement])) {
            return '';
        }

        foreach ($scriptsByPlacement[$placement] as $script) {

            $attributes = $script['attributes'] ?? [];
            $attributes['src'] = $script['src'];

            if ($nonce !== null) {
                $attributes['nonce'] = $nonce;
            }

            $output .= "<script"
                . $this->renderAttributes($attributes)
                . "></script>\n";
        }

        return $output;
    }


    private function renderInlineScripts(string $placement): string
    {
        $output = '';
        $nonce  = $this->html->nonce();

        $scriptsByPlacement = $this->html->inlineScripts();

        if (!isset($scriptsByPlacement[$placement])) {
            return '';
        }

        foreach ($scriptsByPlacement[$placement] as $script) {

            $content = $script['content'] ?? '';
            $attributes = $script['attributes'] ?? [];

            if ($nonce !== null) {
                $attributes['nonce'] = $nonce;
            }

            $output .= "<script"
                . $this->renderAttributes($attributes)
                . ">\n"
                . $content
                . "\n</script>\n";
        }

        return $output;
    }
}
