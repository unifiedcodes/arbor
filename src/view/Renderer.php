<?php

namespace Arbor\view;


use Arbor\view\ViewStack;
use Arbor\view\HtmlRenderer;
use Arbor\view\SchemeRegistry;
use Arbor\view\DocumentNormalizer;
use RuntimeException;
use Throwable;


final class Renderer
{
    public function __construct(
        private SchemeRegistry $schemes,
        private ?string $defaultAssetScheme = null
    ) {}


    public function document(ViewStack $stack): string
    {
        if (!$stack->hasDocument()) {
            throw new RuntimeException('Cannot render: no document set.');
        }

        $initialLevel = ob_get_level();
        ob_start();

        try {
            $document = $stack->getDocument();

            $body = $this->component($document->component(), $stack);

            $html = (
                new DocumentNormalizer(
                    $this->schemes,
                    $this->defaultAssetScheme
                )
            )->normalize($document);

            $output = (new HtmlRenderer($html, $body))->render();

            ob_end_clean();

            return $output;
        } catch (Throwable $e) {

            // clean any buffers created during render
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }

            throw $e;
        }
    }


    public function component(Component $component, ViewStack $stack): string
    {
        $data = $component->data();

        $file = $this->schemes->resolveView($component->uri());

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


    private function evaluate(string $file, array $data): string
    {
        ob_start();

        (function () use ($file, $data) {
            foreach ($data as $key => $value) {

                if (!is_string($key) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
                    throw new RuntimeException(
                        "Invalid variable name '{$key}' passed to view."
                    );
                }

                ${$key} = $value;
            }

            include $file;
        })();

        return ob_get_clean();
    }
}
