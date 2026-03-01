<?php

namespace Arbor\view;


use Arbor\support\path\Uri;
use Arbor\view\ViewStack;
use Arbor\view\HtmlRenderer;
use Arbor\view\SchemeRegistry;
use Arbor\view\DocumentNormalizer;
use RuntimeException;


final class Renderer
{
    public function __construct(private SchemeRegistry $schemes) {}


    public function document(ViewStack $stack): string
    {
        if (!$stack->hasDocument()) {
            throw new RuntimeException('Cannot render: no document set.');
        }

        $document = $stack->getDocument();

        $body = $this->component($document->component(), $stack);

        $html = (new DocumentNormalizer($this->schemes))->normalize($document);

        return (new HtmlRenderer($html, $body))->render();
    }


    public function component(Component $component, ViewStack $stack): string
    {
        $data = $component->data();

        $file = $this->resolveUri($component->uri());

        $stack->pushRendering($component);

        $initialLevel = ob_get_level();

        try {
            $result = $this->evaluate($file, $data);

            if ($component->hasOpenCaptures()) {

                $open = $component->openCaptures();

                $frames = array_map(function ($frame) {
                    return "{$frame['type']} '{$frame['name']}'";
                }, $open);

                $chain = implode(' -> ', $frames);

                throw new RuntimeException(
                    "Unclosed capture(s) in component '{$file}'. "
                        . "Open stack: {$chain}. "
                        . "Did you forget to call endSlot() or endPush()?"
                );
            }

            $currentLevel = ob_get_level();

            if ($currentLevel !== $initialLevel) {

                $difference = $currentLevel - $initialLevel;

                throw new RuntimeException(
                    "Output buffer level mismatch in component '{$file}'. "
                        . "Expected level {$initialLevel}, got {$currentLevel}. "
                        . "Difference: {$difference}. "
                        . "Possible causes: missing endSlot(), missing endPush(), "
                        . "or manual ob_start()/ob_end_*() inside template."
                );
            }

            return $result;
        } finally {
            $stack->popRendering();
        }
    }


    private function normalizeViewFile(string $root, string $relative): string
    {
        $relative = ltrim($relative, '/');

        // If no extension present, append .php
        if (pathinfo($relative, PATHINFO_EXTENSION) === '') {
            $relative .= '.php';
        }

        return normalizeFilePath($root . $relative);
    }


    private function resolveUri(Uri $uri): string
    {
        $schemeName = $uri->scheme();
        $relative   = ltrim($uri->path(), '/');

        $scheme = $this->schemes->get($schemeName);

        $file = $this->normalizeViewFile($scheme->root(), $relative);

        if (!is_file($file)) {
            throw new RuntimeException("View file not found: '{$file}'");
        }

        return $file;
    }


    private function evaluate(string $file, array $data): string
    {
        ob_start();

        (function () use ($file, $data) {
            foreach ($data as $key => $value) {
                ${$key} = $value;
            }

            include $file;
        })();

        return ob_get_clean();
    }
}
