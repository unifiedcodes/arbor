<?php

use Arbor\facades\View;
use Arbor\view\Document;


if (!function_exists('startComponent')) {
    function startComponent(string $uri, array $data = []): void
    {
        View::startComponent($uri, $data);
    }
}

if (!function_exists('endComponent')) {
    function endComponent(): void
    {
        View::endComponent();
    }
}

if (!function_exists('startSlot')) {
    function startSlot(string $name): void
    {
        View::startSlot($name);
    }
}

if (!function_exists('endSlot')) {
    function endSlot(): void
    {
        View::endSlot();
    }
}

if (!function_exists('startPush')) {
    function startPush(string $name): void
    {
        View::startPush($name);
    }
}

if (!function_exists('endPush')) {
    function endPush(): void
    {
        View::endPush();
    }
}

if (!function_exists('slot')) {
    function slot(string $name = 'default'): string
    {
        return View::slot($name);
    }
}

if (!function_exists('render')) {
    function render(string $uri, array $data = []): string
    {
        return View::render($uri, $data);
    }
}

if (!function_exists('document')) {
    function document(): Document
    {
        return View::document();
    }
}

if (!function_exists('component')) {
    function component(string $uri, array $data = []): string
    {
        return View::component($uri, $data);
    }
}
