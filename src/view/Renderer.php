<?php

namespace Arbor\view;


use Arbor\support\path\Uri;
use Arbor\view\ViewStack;
use RuntimeException;


final class Renderer
{
    private SchemeRegistry $schemes;

    public function __construct(SchemeRegistry $schemes)
    {
        $this->schemes = $schemes;
    }


    public function document(ViewStack $stack): string
    {
        if (!$stack->hasDocument()) {
            throw new RuntimeException('Cannot render: no document set.');
        }

        $document = $stack->getDocument();

        $component = $this->component($document->component(), $stack);

        return $component;
    }


    public function component(Component $component, ViewStack $stack): string
    {
        $data = $component->data();

        $file = $this->resolveUri($component->uri());

        $stack->pushRendering($component);

        try {
            return $this->evaluate($file, $data);
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

        extract($data, EXTR_SKIP);

        include $file;

        return ob_get_clean();
    }
}
